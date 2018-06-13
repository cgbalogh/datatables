<?php
namespace CGB\Datatables\ViewHelpers;

class ColViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	* @param array $row
	* @param int $index
	* @return string
	*/
	public function render ($row, $index) {
        $value = $row[$index];
        if (is_array($value)) {
            return implode ('<br \>', $value);
        } else {
            return $value;
        }
	}

}