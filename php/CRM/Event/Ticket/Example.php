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

class CRM_Event_Ticket_Example extends CRM_Event_Ticket {
    
    public function __construct() {
        
        $this->format = array(
            'name'         => $this->name(), 
            'paper-size'   => 'A4',
            'metric'       => 'mm', 
            'margin-top'   => 0, 
            'margin-left'  => 0,
            'margin-right' => 0,
            'font-size'    => 8,
            'orientation'  => 'P',
            'multiple'     => array(
                'cols' => 1,
                'rows' => 8
            )
        );
        
        parent::__construct();

    }
    
    public function name() {
        return ts('Example');
    }
    

    public function printHeader() {
        
        $pdf = &$this->pdf;
        
        if ($this->preview) {
            
            $this->event['title']                        = 'Test Event';
            $this->contact['first_name']                 = 'John';
            $this->contact['last_name']                  = 'Smith';
            $this->event['id']                           = 1;
            $this->contact['id']                         = 1;
            $this->participant['id']                     = 1;
            $this->contribution['id']                    = 1;
            $this->contribution['total_amount']          = '25.00';
            $this->participant['participant_fee_amount'] = '25.00';
            $this->participant['participant_fee_level']  = array('Adult');

            $this->event['address'] = array(
                'street_address'         => 'Test Event Title',
                'supplemental_address_1' => '133 Test Street, Test Town' 
            );

            $this->event['start_date'] = date('c');

        } 

        $pdf->addTTFfont($this->fonts_dir . '/Verdana.ttf', '', '', 32);
        $pdf->addTTFfont($this->fonts_dir . '/Verdana Bold.ttf', '', '', 32);
        $pdf->addTTFfont($this->fonts_dir . '/Verdana Italic.ttf', '', '', 32);
        $pdf->addTTFfont($this->fonts_dir . '/Verdana Bold Italic.ttf', '', '', 32);

    }

     public function printBody() {
        
        $pdf = &$this->pdf;

        //$background_color = array(6, 3, 98);        // dark blue
        $background_color = array(225, 225, 225);     // grey
        $white            = array(255, 255, 255); 
        
        $border_style = array(
            'all' => array(
                'width' => 0.2, 
                'color' => array(75, 75, 75)
            )
        ); 

        $barcode_style = array(
            'position'     => '',
            'align'        => 'C',
            'stretch'      => false,
            'fitwidth'     => true,
            'cellfitalign' => '',
            'border'       => false,
            'hpadding'     => 'auto',
            'vpadding'     => 'auto',
            'fgcolor'      => array(0,0,0),
            'bgcolor'      => false,
            'text'         => true,
            'font'         => 'helvetica',
            'fontsize'     => 8,
            'stretchtext'  => 4
        );

        // ticket background
        $pdf->Rect($pdf->left + 12.2, $pdf->top + 21.8, 191, 54, 'DF', $border_style, $background_color);
        // mark out ticket regions in white
        $pdf->Rect($pdf->left + 17.1, $pdf->top + 25.2, 158.7, 9.2, 'DF', $border_style, $white);
        $pdf->Rect($pdf->left + 17.1, $pdf->top + 37.2, 37, 35.7, 'DF', $border_style, $white);
        $pdf->Rect($pdf->left + 56.5, $pdf->top + 37.2, 79, 35.7, 'DF', $border_style, $white);
        $pdf->Rect($pdf->left + 138, $pdf->top + 37.2, 37.7, 35.7, 'DF', $border_style, $white);
        $pdf->Rect($pdf->left + 178, $pdf->top + 25.2, 20.5, 47.7, 'DF', $border_style, $white);

        $pdf->SetXY($pdf->left + 23.5, $pdf->top + 26.2);
        $pdf->SetFont('helvetica', '', $pdf->fontSize + 7);
        $pdf->MultiCell(100, 9.2, 'GENERAL ADMISSION', 0, 'L', true, 1, '', '', true, 0, false, true, 0, 'M');
        
        $pdf->SetXY($pdf->left + 110, $pdf->top + 28.1);
        $pdf->SetFont('helvetica', '', $pdf->fontSize);
        $pdf->MultiCell(40, '', 'TICKET TYPE: ' . reset($this->participant['participant_fee_level']), 0, 'L', true, 1, '', '', true, 0, false, true, 0, 'M');

        $fee_amount = $this->participant['participant_fee_amount'] / (1 + $this->additional_participants_same_person);
        $pdf->SetXY($pdf->left + 126.8, $pdf->top + 28.1);
        $pdf->SetFont('helvetica', '', $pdf->fontSize);
        $pdf->MultiCell(40, '', CRM_Utils_Money::format($fee_amount), 0, 'R', true, 1, '', '', true, 0, false, true, 0, 'M');

        // left panel - customer info
        
        $pdf->SetFont('helvetica', '', $pdf->fontSize - 0.5);
        $line_spacing = 1.5;

        $pdf->SetXY($pdf->left + 141.3, $pdf->top + 41);
        $pdf->MultiCell(30, 0.3, 'CIVI', 0, 'L', true, 1, '', '', true, 0, false, true, 0, 'M');
        $pdf->SetXY($pdf->left + 141.3, $pdf->GetY() + $line_spacing);
        $pdf->MultiCell(30, 0.3, 'CF# ' . str_pad($this->contribution['id'], 6, '0', STR_PAD_LEFT), 0, 'L', true, 1, '', '', true, 0, false, true, 0, 'M');
        $pdf->SetXY($pdf->left + 141.3, $pdf->GetY() + $line_spacing);
        $pdf->MultiCell(30, 0.3, 'PID: ' . str_pad($this->contact['id'], 6, '0', STR_PAD_LEFT), 0, 'L', true, 1, '', '', true, 0, false, true, 0, 'M');
        //$pdf->SetXY($pdf->left + 73.3, $pdf->GetY() + $line_spacing);
        //$pdf->MultiCell(30, 0.3, $this->contact['last_name'] . ', ' . $this->contact['first_name'], 0, 'L', true, 1, '', '', true, 0, false, true, 0, 'M');
        $pdf->SetXY($pdf->left + 141.3, $pdf->GetY() + $line_spacing);
        $pdf->MultiCell(30, 0.3, date('mdyHi', strtotime($this->participant['participant_register_date'])), 0, 'L', true, 1, '', '', true, 0, false, true, 0, 'M');
        
        $main_text[] = $this->event['title'];

        if (isset($this->event['address']['street_address']) and $this->event['address']['street_address'])
            $main_text[] = $this->event['address']['street_address'];
        if (isset($this->event['address']['supplemental_address_1']) and $this->event['address']['supplemental_address_1'])
            $main_text[] = $this->event['address']['supplemental_address_1'];
        if (isset($this->event['address']['city']) and $this->event['address']['city'])
            $main_text[] = $this->event['address']['city'];

        $main_text[] = date('d M Y h:i A', strtotime($this->event['start_date']));

        // centre column (address)
        $pdf->WriteHTMLCell(60, 30, $pdf->left + 59, $pdf->top + 39.5, 
            "<div style=\"line-height:0.48mm;\">" . implode('<br />', $main_text) . "</div>",
        0, 0, false, 1, '');

        // Footer / QR Code
        $barcode_number = $this->generateHexEventId($this->event['id']) . $this->generateObfuscatedParticipantId($this->participant['id']);
        $pdf->write2DBarcode($barcode_number, 'QRCODE,H', 19.5, $pdf->top + 39, 32, 32);

        $pdf->SetXY($pdf->left + 180.2, $pdf->top + 71.5);
        $pdf->StartTransform();
        $pdf->Rotate(90);
        $pdf->write1DBarcode($barcode_number, 'C128A', '', '', 45, 16, 0.4, $barcode_style, 'N');
        $pdf->StopTransform();
         
    }

    public function printFooter() {
        // I am not here
    }

    protected function generateHexEventId($event_id) {
        // maximum ids we can support is 65536 - 4096
        if ($event_id >= (65536 - 8192))
            return '0000';
        // we can't use 0 or 1 as the first character, so we need to start at 8192 (hex: 2000)
        return strtoupper(dechex($event_id + 8192));
    }

    // client doesn't want the second id to be sequential, so we're going to convert to 7 place hex, 
    // reverse and add a mod 10 check digit as the 8th digit
    protected function generateObfuscatedParticipantId($participant_id) {
        
        $pid = strtoupper(dechex($participant_id));
        $pid = str_pad($pid, 7, '0', STR_PAD_LEFT);
        $pid = strrev($pid);
        return $pid . $this->mod10($participant_id);
    
    }

    protected function mod10($number) {
        
        $sum    = 0;
        $parity = strlen((string)$number) % 2;
        for ($i = strlen((string)$number)-1; $i >= 0; $i--) {
            $digit = $number[$i];
            if (!$parity == ($i % 2)) {
                $digit <<= 1;
            }
            $digit = ($digit > 9) ? ($digit - 9) : $digit;
            $sum += $digit;
        }
        return $sum % 10;
    
    }

}