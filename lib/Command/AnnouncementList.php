<?php
/** TODO License
 * 
 */
namespace OCA\AnnouncementCenter\Command;

use OCA\AnnouncementCenter\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnouncementList extends Command
{
    protected Manager $manager;
    protected int $ulimitAnnouncements = 100;
    public function __construct(Manager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    protected function configure(): void
    {
        //add(string $subject, string $message, string $plainMessage, array $groups, bool $activities, bool $notifications, bool $emails, bool $comments): DataResponse {

        $this
            ->setName('announcementcenter:list')
            ->setDescription('List all announcements');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $announcements = $this->manager->getAnnouncements(0, $this->ulimitAnnouncements);
        foreach ($announcements as $index => $ann) {
            if($index === $this->ulimitAnnouncements - 1) {
                $output->writeln("And more ...");
                break;
            }
            $id = str_pad($ann->getId(), 4, " ", STR_PAD_LEFT);
            $subject = str_pad($ann->getParsedSubject(), 32, " ", STR_PAD_LEFT);
            $subject = strlen($subject) > 32 ? substr($subject, 0, 32 - 3) . "..." : $subject;
            $output->writeln($id . ": " . $subject);
            
        }
        return $this::SUCCESS;
    }
}