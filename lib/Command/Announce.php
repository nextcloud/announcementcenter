<?php
/** TODO License
 * 
 */
namespace OCA\AnnouncementCenter\Command;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\NotificationType;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Announce extends Command
{
    protected IUserManager $userManager;
    protected ITimeFactory $time;
    protected Manager $manager;
    protected NotificationType $notificationType;
    protected LoggerInterface $logger;
    public function __construct(IUserManager $userManager, ITimeFactory $time, Manager $manager, NotificationType $notificationType, LoggerInterface $logger)
    {
        parent::__construct();
        $this->userManager = $userManager;
        $this->time = $time;
        $this->manager = $manager;
        $this->notificationType = $notificationType;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('announcementcenter:announce')
            ->setDescription('Create an announcement')
            ->addArgument(
                'user',
                InputArgument::REQUIRED,
                'User who creates the announcement',
            )
            ->addArgument(
                'subject',
                InputArgument::REQUIRED,
                'User for whom the addressbook will be created',
            )
            ->addArgument(
                'message',
                InputArgument::REQUIRED,
                'message of the announcement (supports markdown)',
            )
            ->addArgument(
                'groups',
                InputArgument::REQUIRED,
                'Comma seperated list of groups who get informed (no space between)',
            )
            ->addArgument(
                'activities',
                InputArgument::REQUIRED,
                'Get notified over activities',
            )
            ->addArgument(
                'notifications',
                InputArgument::REQUIRED,
                'Get notified over nextclouds notifications',
            )
            ->addArgument(
                'emails',
                InputArgument::REQUIRED,
                'Notify users over email',
            )
            ->addArgument(
                'comments',
                InputArgument::REQUIRED,
                'Allow comments',
            )
            ->addArgument(
                'schedule-time',
                InputArgument::OPTIONAL,
                'Publishing time of the announcement (see php strtotime)',
                null,
            )
            ->addArgument(
                'delete-time',
                InputArgument::OPTIONAL,
                'Deletion time of the announcement (see php strtotime)',
                null,
            );
    }

    private function plainifyMessage(string $message)
    {
        # TODO use Parsedown or Markdownify here
        return $message;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = $input->getArgument('user');
        if (!$this->userManager->userExists($user)) {
            throw new \InvalidArgumentException("User <$user> in unknown.");
        }
        $subject = $input->getArgument('subject');
        $message = $input->getArgument('message');
        $groups = explode(",", $input->getArgument('groups'));
        $activities = $this->parseBoolean($input->getArgument('activities'));
        $notifications = $this->parseBoolean($input->getArgument('notifications'));
        $emails = $this->parseBoolean($input->getArgument('emails'));
        $comments = $this->parseBoolean($input->getArgument('comments'));
        $scheduleTime = $this->parseTimestamp($input->getArgument('schedule-time'));
        $deleteTime = $this->parseTimestamp($input->getArgument('delete-time'));

        if($scheduleTime && $deleteTime && $deleteTime < $scheduleTime) {
            throw new \InvalidArgumentException("Publishing time is after deletion time");
        }

        $plainMessage = $this->plainifyMessage($message);
        $notificationOptions = $this->notificationType->setNotificationTypes($activities, $notifications, $emails);

        $result = $this->manager->announce($subject, $message, $plainMessage, $user, $this->time->getTime(), $groups, $comments, $notificationOptions, $scheduleTime, $deleteTime);
        $output->writeln("Created announcement #" . $result->getId() . ": " . $result->getSubject());
        $this->logger->info('Admin ' . $user . ' posted a new announcement: "' . $result->getSubject() . '" over CLI');
        return $this::SUCCESS;
    }

    private function parseBoolean($argument): bool
    {
        $argument = strtolower($argument);
        if ($argument === "y" || $argument === "yes" || $argument === "true") {
            return true;
        } elseif ($argument === "n" || $argument === "no" || $argument === "false") {
            return false;
        }
        throw new \InvalidArgumentException("Could not interprete '" . $argument . "'");
    }

    private function parseTimestamp($argument) : int|null
    {
        if(is_null($argument)) {
            return null;
        }
        if (($timestamp = strtotime($argument)) === false) {
            throw new \InvalidArgumentException("Could not interprete time '" . $argument . "'");
        }
        if($timestamp < $this->time->getTime()) {
            throw new \InvalidArgumentException("Time '". $argument . "' is not allowed, because it's in the past");
        }
        return $timestamp;
    }
}