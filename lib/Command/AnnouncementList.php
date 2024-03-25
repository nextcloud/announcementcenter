<?php
/** TODO License
 * 
 */
namespace OCA\AnnouncementCenter\Command;

use OCA\AnnouncementCenter\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnouncementList extends Command
{
    protected Manager $manager;
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
            ->setDescription('List all announcements')
            ->addArgument(
                'limit',
                InputArgument::OPTIONAL,
                'Maximal number of announcements listed',
                10,
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ulimit = $input->getArgument('limit');
        if (!is_numeric($ulimit)) {
            throw new \InvalidArgumentException('"' . $ulimit . '" is not numeric');
        }
        $ulimit = intval($ulimit);
        $announcements = $this->manager->getAnnouncements(0, $ulimit + 1);
        foreach ($announcements as $index => $ann) {
            if ($index === $ulimit) {
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