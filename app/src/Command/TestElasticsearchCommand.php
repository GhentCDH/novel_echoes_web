<?php

namespace App\Command;

use App\Model\Text;
use App\Repository\TextRepository;
use App\Resource\ElasticCommunicativeGoalResource;
use App\Resource\ElasticSearch\ElasticTextResource;
use App\Service\ElasticSearch\Index\TextIndexService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TestElasticsearchCommand extends Command
{
    protected static $defaultName = 'app:elasticsearch:test';
    protected static $defaultDescription = 'Test elasticsearch item representation.';

    protected $container = [];
    protected $di = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var $repository TextRepository */
        $repository = $this->container->get('text_repository');

        /** @var $text Text */
        $text = $repository->find(8);

        $res = new ElasticTextResource($text);

        dump($res->toJson(JSON_PRETTY_PRINT));
        return Command::SUCCESS;
    }
}
