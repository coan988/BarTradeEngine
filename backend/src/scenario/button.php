<?php
class Button{
    private array $scenarios = [];
    private array $cachedScenario = [];
    private array $currentScenario = [];

    function __construct(){
        $buttonfunc = get_defined_functions()['user'];

        include_once __DIR__ .'/scenario.php';
        $scenariofunc = get_defined_functions()['user'];    

        $this->scenarios = array_values(array_diff($scenariofunc, $buttonfunc));

        if (empty($this->scenarios)){
            throw new RuntimeException("Keine Funktionen vorhanden.");
        }
    }

    public function getScenarios(): array {
        return $this->scenarios;
    }

    function chooseScenario(){
        $index = array_rand($this->scenarios);
        $scenarioName = $this->scenarios[$index];
        $scenarioData = $scenarioName();

        if (in_array($scenarioName, $this->cachedScenario)){
            return $this->chooseScenario();
        }
        $this->currentScenario = $scenarioData;
        $this->cache($scenarioName);
        
        return $scenarioData;
    }

    function cache(string $scenarioName): void{
        $this->cachedScenario[] = $scenarioName;
        if (count($this->cachedScenario) > 3){
            array_shift($this->cachedScenario);
        }
    }

    public function run(): array{
        require_once __DIR__ . '/../pricebuilding/priceservice.php';
        $scenario = $this->chooseScenario();
        $applyScenario = new Scenario();
        $applyScenario->run($scenario);
        return $scenario;
    }
}