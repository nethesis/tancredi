<?php namespace Tancredi\Entity;

/***************************************************************
* When invoked, this class add current date to scope variables *
****************************************************************/
class SampleFilter
{
    private $logger;
    private $date_format;

    public function __construct($config,$logger)
    {
        $this->logger = $logger;
        // Filter class can access configurations
        $this->date_format = ($config['samplefilter_format'] ? $config['samplefilter_format'] : 'D, d M Y H:i:s');
    }

    // The __invoke function is called after the filter class is istantiated with the scope variables as argument
    public function __invoke($variables)
    {
        $variables['date'] = date($this->date_format);
        // The __invoke function must return the variables array
        return $variables;
    }
}

