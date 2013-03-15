<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of YsJQueryDinamic
 *
 * @author oyepez
 */
class YsJQueryDynamic extends YsJQuery {

  public function __construct($function = null) {
    parent::__construct();
    if ($function !== null) {
      $arraySintax = func_get_args();
      $this->build($arraySintax);
    }
    return $this;
  }

  public function build($function) {
    if ($function !== null) {
      if (sizeof($function) > 1) {
        $this->clearJQueryList();
        $jqueryFinal = new YsJQuerySintax();
        $argAux = $function[0];
        $postSintax = null;
        for ($i = 1; $i < sizeof($function); $i++) {
          $argNext = $function[$i];
          if ($argNext instanceof YsJQueryBuilder) {
            if ($argNext->isOnlyAccesors()) {
              $argAux->addPostSintax(YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR . $argNext);
            }
            if (($argNext->getSelector() == $argAux->getSelector() || $argNext->getSelector() == null)) {
              if (!$argNext->isOnlyAccesors()) {
                $argAux->addAccesorsWithPattern($argNext->getEvent(), $argNext->getArguments());
              }
            } else {
              $argAux->addPostSintax($postSintax);
              $postSintax = null;
              $this->getJQueryList()->add($argAux->getSelector(), $argAux);
              $argAux = $function[$i];
            }
          } else {
            $argAux->addPostSintax(YsJsFunction::JAVASCRIPT_SINTAX_SEPARATOR . $argNext);
          }
        }
        $this->getJQueryList()->add($argAux->getSelector(), $argAux);
        $sintax = '';
        foreach ($this->getJQueryList()->getItems() as $jquery => $jquerySintax) {
          $sintax .= $jquerySintax;
        }
        $this->setFunction($sintax);
      } else {
        if (is_array($function) && isset($function[0])) {
          $this->setFunction($function[0]);
        } else {
          $this->setFunction($function);
        }
      }
      $this->jquery->render();
    }
    return $this;
  }

  public function __toString() {
    return $this->jquery->getInternalSintax();
  }

}
