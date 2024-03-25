<?php
/** TODO License
 * 
 */
namespace OCA\AnnouncementCenter\Command;

use OCA\AnnouncementCenter\Manager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnouncementDelete extends Command
{
    protected Manager $manager;
    protected LoggerInterface $logger;
    public function __construct(Manager $manager, LoggerInterface $logger)
    {
        parent::__construct();
        $this->manager = $manager;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('announcementcenter:delete')
            ->setDescription('List all announcements')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Id of announcement to delete',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deleteId = $input->getArgument('id');
        $this->manager->delete($deleteId);
        $output->writeln("Deleted #" . $deleteId);
        $this->logger->info('Admin deleted announcement #' . $deleteId . ' over CLI');
        return $this::SUCCESS;
    }
}