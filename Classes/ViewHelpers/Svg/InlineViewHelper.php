<?php

namespace Cext\Play\ViewHelpers\Svg;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Christian Pschorr <pschorr.christian@gmail.com>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * View helper to render a SVG icon inline.
 */
class InlineViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * TYPO3's configuration manager
	 *
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * Initialize all arguments with their description and options.
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('preserveAspectRatio', 'string', 'Alignment and scaling options from the SVG standard', false, null);
	}

	/**
	 * liesst eine SVG aus dem Dateisystem aus und gibt den Code Inline aus. Startet der Pfad mit einem / wird von einem
	 * absoluten Pfad ausgegangen. Ansonsten wird versucht das SVG im Standard SVG-Ordner (siehe TS) zu finden.
	 *
	 * @param string $source
	 * @return boolean|array
	 */
	protected function render($source) {
		$path = $this->resolvePath($source);
		$output = file_get_contents($path);

		if($output === false) {
			return '<!-- SVG: ' . $source . ' could not be found -->';
		}

		// Add additional classes to the SVG.
		$preserveAspectRatio = trim($this->arguments['preserveAspectRatio']);
		$output = preg_replace_callback('/<svg.*?>/s', function(array $matches) use ($preserveAspectRatio) {
			$content = $matches[0];

			// Handle 'preserveAspectRatio' attribute.
			if(!empty($preserveAspectRatio)) {
				if(preg_match('/\spreserveAspectRatio="[^"]*"/', $matches[0])) {
					$content = preg_replace('/(\s)preserveAspectRatio="[^"+]"/', '\\1preserveAspectRatio="' . $preserveAspectRatio . '"', $content);
				} else {
					$content = str_replace('<svg', '<svg preserveAspectRatio="' . $preserveAspectRatio . '"', $content);
				}
			}

			return $content;
		}, $output);

		return $output;
	}

	/**
	 * @param string $path;
	 * @return string
	 */
	protected function resolvePath($path) {
		$settings = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'play');

		if(strpos($path, '/') !== 0) {
			$path = $settings['svg']['path'] . $path;

		// /fileadmin/... wird zu fileadmin und kann damit ueber GeneralUtility::getFileAbsFileName aufgeloest werden
		} else {
			$path = trim($path, '/');
		}

		return \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($path);
	}
}
