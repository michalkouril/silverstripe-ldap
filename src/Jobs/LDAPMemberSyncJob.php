<?php

namespace SilverStripe\LDAP\Jobs;

use Exception;
use SilverStripe\LDAP\Tasks\LDAPMemberSyncTask;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\PolyExecution\PolyOutput;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class LDAPMemberSyncJob
 *
 * A {@link QueuedJob} job to sync all users to the site using LDAP.
 * This doesn't do the actual sync work, but rather just triggers {@link LDAPMemberSyncTask}
 */
class LDAPMemberSyncJob extends AbstractQueuedJob
{
    /**
     * If you specify this value in seconds, it tells the completed job to queue another of itself
     * x seconds ahead of time.
     *
     * @var mixed
     * @config
     */
    private static $regenerate_time = null;

    public function __construct()
    {
        // noop, but needed for QueuedJobsAdmin::createjob() to work
    }

    /**
     * @return string
     */
    public function getJobType()
    {
        return QueuedJob::QUEUED;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return _t(__CLASS__ . '.SYNCTITLE', 'Sync all users from Active Directory');
    }

    /**
     * @return string
     */
    public function getSignature()
    {
        return md5(get_class($this));
    }

    /**
     * @throws Exception
     */
    public function validateRegenerateTime()
    {
        $regenerateTime = Config::inst()->get(
            LDAPMemberSyncJob::class,
            'regenerate_time'
        );

        // don't allow this job to run less than every 15 minutes, as it could take a while.
        if ($regenerateTime !== null && $regenerateTime < 900) {
            throw new Exception('LDAPMemberSyncJob::regenerate_time must be 15 minutes or greater');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function process()
    {
        $regenerateTime = Config::inst()->get(
            LDAPMemberSyncJob::class,
            'regenerate_time'
        );
        if ($regenerateTime) {
            $this->validateRegenerateTime();

            $nextJob = Injector::inst()->create(LDAPMemberSyncJob::class);
            singleton(QueuedJobService::class)->queueJob($nextJob, date('Y-m-d H:i:s', time() + $regenerateTime));
        }

        $task = Injector::inst()->create(LDAPMemberSyncTask::class);
        $output = PolyOutput::create(PolyOutput::FORMAT_ANSI);
        $input = new ArrayInput([]);
        $input->setInteractive(false);
        $task->run($input, $output);

        $this->isComplete = true;
    }
}
