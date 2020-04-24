<?php

namespace UWebPro\Wordpress;

use Cron\CronExpression;

class WPSchedule
{
    use ManagesFrequencies;

    /**
     * @var WPSchedule
     */
    private static $instance;

    protected $expression = '* * * * *';

    public $timezone;

    private $crons = [];

    private $task;

    public function __construct()
    {
        add_filter('cron_schedules', function ($schedules) {
            if (!isset($schedules["1min"])) {
                $schedules["1min"] = [
                    'interval' => 60,
                    'display' => __('Once every minute')
                ];
            }

            return $schedules;
        });
        $this->timezone = date_default_timezone_get();

        if (!wp_next_scheduled('uwebpro_schedule_action')) {
            wp_schedule_event(time(), '1min', 'uwebpro_schedule_action');
        }

        add_action('uwebpro_schedule_action', function () {
            foreach ($this->crons as $cron) {
                if (CronExpression::factory($cron['tab'])->isDue()) {
                    $cron['callback']();
                }
            }
        });
    }

    public static function instance(): self
    {
        return self::$instance;
    }

    public function schedule($callback)
    {
        $this->task = $callback;

        self::$instance = $this;

        return $this;
    }

    protected function add(string $expression)
    {
        $this->crons[] = ['tab' => $expression, 'callback' => $this->task];
    }
}