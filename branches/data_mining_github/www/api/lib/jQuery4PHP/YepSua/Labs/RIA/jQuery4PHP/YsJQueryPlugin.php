<?php
/*
 * This file is part of the YepSua package.
 * (c) 2009-2010 Omar Yepez <omar.yepez@yepsua.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * YsJLayout todo description
 *
 * @package    YepSua
 * @subpackage jQuery4PHP
 * @author     Omar Yepez <omar.yepez@yepsua.com>
 * @version    SVN: $Id$
 */
class YsJQueryPlugin extends YsJQueryUtil {

  public function registerOptions(){}

  /**
   * Render function for jLayout functionality
   * @see YsJQueryUtil::render()
   * @return parent::render()
   */
  public function render(){
    if($this->getOptions() !== null){
      if($this->getArgumentsBeforeOptions() !== null || $this->getArgumentsAfterOptions() !== null){
        $this->setArguments($this->getArgumentsBeforeOptions() .  $this->getOptionsLikeJson() . $this->getArgumentsAfterOptions());
      }else{
        $this->setArguments($this->getOptionsLikeJson());
      }
    }
    return parent::render();
  }
}