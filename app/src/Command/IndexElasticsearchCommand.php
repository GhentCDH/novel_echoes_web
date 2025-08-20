<?php

namespace App\Command;

use App\Model\Text;
use App\Repository\TextRepository;
use App\Repository\RepositoryInterface;
use App\Resource\ElasticSearch\ElasticTextResource;
use App\Resource\ElasticSearch\ElasticTraditionResource;
use App\Service\ElasticSearch\Index\TextIndexService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IndexElasticsearchCommand extends Command
{
    protected static $defaultName = 'app:elasticsearch:index';
    protected static $defaultDescription = 'Drops the old elasticsearch index and recreates it.';

    protected $container = [];
    protected $di = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('index', InputArgument::REQUIRED, 'Which index should be reindexed?')
            ->addArgument('maxItems', InputArgument::OPTIONAL, 'Max number of items to index')
            ->setHelp('This command allows you to reindex elasticsearch.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $chunkSize = 200;

        $count = 0;
        $maxItems = $input->getArgument('maxItems');
        if ($index = $input->getArgument('index')) {
            switch ($index) {
                case 'text':
                    /** @var $repository TextRepository */
                    $repository = $this->container->get('text_repository' );

                    /** @var $service TextIndexService */
                    $service = $this->container->get('text_index_service');
                    $indexName = $service->createNewIndex();

                    $total = $repository->indexQuery()->count();

                    // go!
                    $progressBar = new ProgressBar($output, $total);
                    $progressBar->start();

                    $repository->indexQuery()->chunk(100,
                        function($charters) use ($service, &$count, $progressBar, $maxItems, $chunkSize): bool {
                            if ( $maxItems && $count >= $maxItems ) {
                                return false;
                            }

                            // index charters
                            $charterResources = ElasticTextResource::collection($charters);
                            $count += $charterResources->count();
                            $service->addMultiple($charterResources);

                            // update progress bar
                            $progressBar->advance($chunkSize);
                            return true;
                        });

                    $service->switchToNewIndex($indexName);

                    $progressBar->finish();

                    break;
            }
        }

        $io->success("Succesfully indexed {$count} records");

        return Command::SUCCESS;
    }
}
