<?php
namespace ikarus\system\template;

/**
 * SetupTemplate loads and displays template in the setup process.
 * 
 * @author 		Marcel Werk
 * @copyright		2001-2009 WoltLab GmbH
 * @package		com.develfusion.ikarus
 * @subpackage		system.template
 * @category		Ikarus Framework
 * @license		GNU Lesser Public License <http://www.gnu.org/licenses/lgpl.txt>
 * @version		1.0.0-0001
 */
class SetupTemplate extends Template {
	
	/**
	 * Contains the directory where the templates are located
	 * @var		string
	 */
	protected $templatePath = '';
	
	/**
	 * @see Template::setTemplatePaths()
	 */
	public function setTemplatePaths($templatePaths) {
		if (is_array($templatePaths)) $this->templatePath = array_shift($templatePaths);
		else $this->templatePath = $templatePaths;
	}
	
	/**
	 * @see Template::loadTemplateStructure()
	 */
	protected function loadTemplateStructure() {}
	
	/**
	 * @see Template::getSourceFilename()
	 */
	public function getSourceFilename($templateName, $packageID = 0) {
		return $this->templatePath.TMP_FILE_PREFIX.$templateName.'.tpl';
	}
	
	/**
	 * @see Template::getCompiledFilename()
	 */
	public function getCompiledFilename($templateName, $packageID = 0) {
		return $this->compileDir.TMP_FILE_PREFIX.$this->languageID.'_'.$templateName.'.php';
	}
	
	/**
	 * @see Template::getPluginFilename()
	 */
	public function getPluginFilename($type, $tag) {
		return $this->pluginDir.TMP_FILE_PREFIX.'TemplatePlugin'.StringUtil::firstCharToUpperCase(StringUtil::toLowerCase($type)).StringUtil::firstCharToUpperCase(StringUtil::toLowerCase($tag)).'.class.php';
	}
	
	/**
	 * @see Template::getCompiler()
	 */
	protected function getCompiler() {
		return new TemplateCompiler($this);
	}
}
?>