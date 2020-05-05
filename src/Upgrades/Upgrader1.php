<?php namespace Tancredi;

class Upgrader1
{
    private $storage;
    private $config;
    private $logger;

    public function __construct($storage,$config,$logger)
    {
        $this->storage = $storage;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function __invoke()
    {
        $this->logger->debug('Upgrading to version 1');

        # Rename models
        $names = array(
            "yealink-T19P_E2" => "yealink-T19",
            "yealink-T21P_E2" => "yealink-T19",
            "yealink-T21P_E2" => "yealink-T21",
            "yealink-T23G" => "yealink-T23",
            "yealink-T27G" => "yealink-T27",
            "yealink-T29G" => "yealink-T29",
            "yealink-T40PG" => "yealink-T40",
            "yealink-T41PS" => "yealink-T41",
            "yealink-T42GS" => "yealink-T42",
            "yealink-T43U" => "yealink-T43",
            "yealink-T46GS" => "yealink-T46",
            "yealink-T48GS" => "yealink-T48",
            "yealink-T49G" => "yealink-T49"
        );
        foreach ($names as $oldname => $newname) {
            # Rename scope files
            if (file_exists($this->config['rw_dir'] . 'scopes/' . $oldname . '.ini')) {
                rename ($this->config['rw_dir'] . 'scopes/' . $oldname . '.ini' , $this->config['rw_dir'] . 'scopes/' . $newname . '.ini');
            }
        }

        # Rename scope parents
        foreach ($this->storage->listScopes($typeFilter = 'phone') as $id) {
            $scope = new \Tancredi\Entity\Scope($id, $this->storage, $this->logger);
            if (array_key_exists($scope->metadata['inheritFrom'], $names)) {
                $scope->metadata['inheritFrom'] = $names[$scope->metadata['inheritFrom']];
		$scope->setVariables();
            }
        }
    }
}
