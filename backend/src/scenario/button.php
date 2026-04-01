<?php
class Button{
    private array $scenarios = [];
    private array $cachscenario  =[];
    private ?string $currentScenario = null;

    function __construct(){
        include_once __DIR__ .'/../db.php';
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
        if ($this->currentScenario === null){
            $index = array_rand($this->scenarios);
            $this->currentScenario = $this->scenarios[$index];
        }
        return $this->currentScenario;
    }

    function cache(string $selection){
    # Cache von z.B. 3 Einführen, damit eine Funktion nicht direckt nochmal gezogen werden kann.
    # dafür erstmal Funktionen schreiben
    }

    public function run(){
        $selection = $this->chooseScenario();
        return $selection();
    }
}