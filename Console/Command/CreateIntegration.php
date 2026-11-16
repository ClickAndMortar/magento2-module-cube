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
 * Crée l'intégration du cube (ressource ClickAndMortar_Cube::stats seule), l'active et affiche son jeton.
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
            ->setDescription("Crée l'intégration du Cube Magento (lecture des statistiques seule) et affiche son jeton")
            ->addOption('name', null, InputOption::VALUE_REQUIRED, "Nom de l'intégration", 'Cube Magento')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'E-mail de contact (facultatif)', '')
            ->addOption('replace', null, InputOption::VALUE_NONE, "Supprime puis recrée l'intégration si elle existe (l'ancien jeton cesse de fonctionner)")
            ->addOption('token-only', null, InputOption::VALUE_NONE, "N'affiche que le jeton (pour les scripts)");
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
                    '<error>L\'intégration « %s » existe déjà (id %d). Relancer avec --replace pour la recréer.</error>',
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
            $output->writeln('<error>Jeton introuvable après création.</error>');
            return Command::FAILURE;
        }

        if ($input->getOption('token-only')) {
            $output->writeln($token->getToken());
            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Intégration « %s » créée (id %d), ressource : %s', $name, $integration->getId(), implode(', ', self::RESOURCES)));
        $output->writeln('Jeton d\'accès : ' . $token->getToken());
        if (!$this->scopeConfig->isSetFlag(self::BEARER_FLAG)) {
            $output->writeln('<comment>Attention : les jetons d\'intégration ne sont pas acceptés comme « Bearer » sur cette boutique.</comment>');
            $output->writeln('<comment>Activer : bin/magento config:set ' . self::BEARER_FLAG . ' 1</comment>');
        }
        return Command::SUCCESS;
    }
}
