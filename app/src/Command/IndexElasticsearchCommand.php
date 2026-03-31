<?php

namespace App\Command;

use App\Repository\TextRepository;
use App\Resource\ElasticSearch\ElasticTextResource;
use App\Service\ElasticSearch\Index\TextIndexService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class IndexElasticsearchCommand extends Command
{
    protected $container = [];
    protected $di = [];

    public function __construct(protected TextIndexService $textIndexService)
    {
        parent::__construct('app:elasticsearch:index');
    }

    protected function configure()
    {
        $this
            ->setDescription('Drops the old elasticsearch index and recreates it.')
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
                    $repository = new TextRepository();

                    $service = $this->textIndexService;
                    $indexName = $service->createNewIndex();

                    $total = $repository->indexQuery()->count();

                    // go!
                    $progressBar = new ProgressBar($output, $total);
                    $progressBar->start();

                    $repository->indexQuery()->chunk(100,
                        function($texts) use ($service, &$count, $progressBar, $maxItems, $chunkSize): bool {
                            if ( $maxItems && $count >= $maxItems ) {
                                return false;
                            }

                            // index texts
                            $textResources = ElasticTextResource::collection($texts);
                            $count += $textResources->count();
                            $service->addMultiple($textResources);

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
