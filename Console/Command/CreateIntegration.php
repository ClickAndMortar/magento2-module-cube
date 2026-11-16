<?php
declare(strict_types=1);

namespace ClickAndMortar\Cube\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Integration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Creates the cube integration (ClickAndMortar_Cube::stats resource only), activates it and prints its token.
 */
class CreateIntegration extends Command
{
    private const RESOURCES = ['ClickAndMortar_Cube::stats'];
    private const BEARER_FLAG = 'oauth/consumer/enable_integration_as_bearer';

    public function __construct(
        private readonly IntegrationServiceInterface $integrationService,
        private readonly OauthServiceInterface $oauthService,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly State $appState
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('cm-cube:integration:create')
            ->setDescription("Create the Cube Magento integration (read-only stats access) and print its access token")
            ->addOption('name', null, InputOption::VALUE_REQUIRED, "Integration name", 'Cube Magento')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Contact email (optional)', '')
            ->addOption('replace', null, InputOption::VALUE_NONE, "Delete and recreate the integration if it exists (the previous token stops working)")
            ->addOption('token-only', null, InputOption::VALUE_NONE, "Print the token only (for scripts)");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->getAreaCode();
        } catch (\Magento\Framework\Exception\LocalizedException) {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        }

        $name = (string) $input->getOption('name');
        $existing = $this->integrationService->findByName($name);
        if ($existing->getId()) {
            if (!$input->getOption('replace')) {
                $output->writeln(sprintf(
                    '<error>Integration "%s" already exists (id %d). Run again with --replace to recreate it.</error>',
                    $name,
                    $existing->getId()
                ));
                return Command::FAILURE;
            }
            $this->integrationService->delete((int) $existing->getId());
        }

        $integration = $this->integrationService->create([
            Integration::NAME => $name,
            Integration::EMAIL => (string) $input->getOption('email'),
            Integration::STATUS => Integration::STATUS_ACTIVE,
            Integration::SETUP_TYPE => Integration::TYPE_MANUAL,
            'resource' => self::RESOURCES,
            'all_resources' => false,
        ]);

        $consumerId = (int) $integration->getConsumerId();
        $this->oauthService->createAccessToken($consumerId, true);
        $token = $this->oauthService->getAccessToken($consumerId);
        if (!$token || !$token->getToken()) {
            $output->writeln('<error>Access token not found after creation.</error>');
            return Command::FAILURE;
        }

        if ($input->getOption('token-only')) {
            $output->writeln($token->getToken());
            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Integration "%s" created (id %d), resource: %s', $name, $integration->getId(), implode(', ', self::RESOURCES)));
        $output->writeln('Access token: ' . $token->getToken());
        if (!$this->scopeConfig->isSetFlag(self::BEARER_FLAG)) {
            $output->writeln('<comment>Warning: integration tokens are not accepted as standalone Bearer tokens on this store.</comment>');
            $output->writeln('<comment>Enable with: bin/magento config:set ' . self::BEARER_FLAG . ' 1</comment>');
        }
        return Command::SUCCESS;
    }
}
