<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace CGB\Datatables\UserFunc;

/**
 * Description of DateCOnverter
 *
 * @author cbalogh
 */
class DateConverter {
    
    const LOWER_LIMIT = false;
    const UPPER_LIMIT = true;

    /**
     * 
     * @param string $dateString
     * @param string $formatString
     * @param bool $upperLimit
     */
    static function convert($dateString = '', $formatString = '', $upperLimit = false) {
        
        if (preg_match('/(?P<y>[0-9]{4})(?P<m>[0-9]{2})?(?P<d>[0-9]{2})?/', $dateString, $matches)) {
            $y = (int) $matches['y'];
            $m = (int) $matches['m'];
            $d = (int) $matches['d'];
            
            $lower = new \DateTime;
            $lower->setTimestamp(mktime(0,0,0,$m,$d,$y));
            
            if ($y == 0) {
                return null;
            } elseif ($m == 0) {
                $lower->add(new \DateInterval('P1D'))->add(new \DateInterval('P1M'));
                $upper = clone $lower;
                $upper->add(new \DateInterval('P1Y'))->sub(new \DateInterval('PT1S'));
            } elseif ($d == 0) {
                $lower->add(new \DateInterval('P1D'));
                $upper = clone $lower;
                $upper->add(new \DateInterval('P1M'))->sub(new \DateInterval('PT1S'));
            } else {
                $upper = clone $lower;
                $upper->add(new \DateInterval('P1D'))->sub(new \DateInterval('PT1S'));
            }
            
            return $upperLimit ? $upper->getTimestamp() : $lower->getTimestamp();
        }
        return null;
    }
    
}
