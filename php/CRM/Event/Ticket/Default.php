<?php

/* 
 * Event Tickets Extension for CiviCRM - Circle Interactive 2013
 * Author: andyw@circle
 *
 * Distributed under the GNU Affero General Public License, version 3
 * http://www.gnu.org/licenses/agpl-3.0.html 
 */

// This class doesn't do much except set some basic defaults. By not overriding
// any of the print methods, we force the default base class methods to be invoked.

class CRM_Event_Ticket_Default extends CRM_Event_Ticket {
    
    public function __construct() {
        $this->format = array(
            'name'         => $this->name(), 
            'paper-size'   => 'A6',
            'metric'       => 'mm', 
            'margin-top'   => 5, 
            'margin-left'  => 5,
            'margin-right' => 5,
            'font-size'    => 11,
            'orientation'  => 'L'
        );
        parent::__construct();
    }
    
    public function name() {
        return ts('Default');
    }
    
}