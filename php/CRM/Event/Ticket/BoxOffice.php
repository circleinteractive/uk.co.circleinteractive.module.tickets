<?php

/* 
 * Event Tickets Extension for CiviCRM - Circle Interactive 2013
 * Author: andyw@circle
 *
 * Distributed under the GNU Affero General Public License, version 3
 * http://www.gnu.org/licenses/agpl-3.0.html 
 */


class CRM_Event_Ticket_BoxOffice extends CRM_Event_Ticket {
    
    public function __construct() {
        
        $this->format = array(
            'name'         => $this->name(), 
            'paper-size'   => 'Letter',
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
        //&$this->pdf = $pdf;
        


    }

    // given a participant id, return a sequential seating number
    // (first person to signup to the event gets 1, second person 2 etc)
    protected function getSeatNumber($participant_id) {

        return CRM_Core_DAO::singleValueQuery("
            SELECT seat_no FROM (
                SELECT @i := @i + 1 AS seat_no, participant_id FROM (
                    SELECT id AS participant_id FROM civicrm_participant
                    WHERE event_id = (
                        SELECT event_id FROM civicrm_participant 
                        WHERE id = %1
                    )
                ) p,
                (SELECT @i := 0) x
            ) s WHERE participant_id = %1
        ", array(
              1 => array($participant_id, 'Integer')
           )
        );

    }
    
    public function name() {
        return ts('Box Office');
    }

    public function printHeader() {
        
        $pdf = &$this->pdf;
        
        if ($this->preview) {
            $this->event['event_title'] = 'Test Event';
            $this->contact['primary']['first_name'] = 'John';
            $this->contact['primary']['last_name'] = 'Smith';
            $this->event['id'] = 1234;
            $this->event['start_date'] = date('c');
            $this->contact['primary']['id'] = 1;
            $this->participant['id'] = 1;
            $this->participant['participant_fee_amount'] = '99.00';
            $this->contribution['total_amount'] = '25.00';
            $this->contribution['payment_instrument'] = 'Credit Card';
        }

        $this->payment_instrument_codes = array(
            'Cash'        => 'CSH',
            'Check'       => 'CHQ',
            'Credit Card' => 'CC',
            'Debit Card'  => 'DC',
            'EFT'         => 'EFT'
        );

        //foreach (array('contact', 'contribution', 'event', 'participant') as $entity)
        //    watchdog('andyw', $entity . ' = <pre>' . print_r($this->$entity, true) . '</pre>');

        //$pdf->addTTFfont(__DIR__ . '/fonts/arcade.ttf', '', '', 32);
        //$pdf->addTTFfont($this->fonts_dir . DIRECTORY_SEPARATOR . 'Arcade.ttf', '', '', 32);
        $pdf->addTTFfont($this->fonts_dir . DIRECTORY_SEPARATOR . 'SourceSansPro-Regular.ttf', '', '', 32);
        $pdf->addTTFfont($this->fonts_dir . DIRECTORY_SEPARATOR . 'SourceSansPro-Semibold.ttf', '', '', 32);
        $pdf->addTTFfont($this->fonts_dir . DIRECTORY_SEPARATOR . 'SourceSansPro-Bold.ttf', '', '', 32);

        $this->primary_font    = 'arcade';
        $this->secondary_font  = 'sourcesansprob';
        $this->barcode_font    = 'sourcesansprosemib';

        $this->primary_font = $pdf->addTTFfont(__DIR__ . '/fonts/VT323-Regular.ttf', '', '', 32);
        
        
        //echo dirname(__FILE__) . '/fonts/dotmatri.php<br />';
        //exit;
        //$pdf->addFont('dotmatri', '', dirname(__FILE__) . '/fonts/dotmatri.php');
        //$pdf->addFont('arcade');
        $pdf->SetFont($this->primary_font, '', $this->fontSize, true);
        $pdf->SetTextColor(64, 64, 64);

    }

    public function printBody() {
        
        $pdf = &$this->pdf;

        $background_color  = array(6, 3, 98);    
        $white             = array(255, 255, 255);
        $yellow            = array(255, 244, 137);
        $orange            = array(230, 106, 70);
        
        $zebra_stripe[0]   = array(248, 248, 235);
        $zebra_stripe[1]   = array(255, 255, 255);

        $dotted_line_style = array(
            'width' => 0.2, 
            'cap'   => 'butt', 
            'join'  => 'miter', 
            'dash'  => 3.5, 
            'color' => array(88, 91, 96)
        ); 

        $box_outline = array(
            'width' => 0.5, 
            'color' => array(232, 230, 153)
        ); 

        $barcode_style     = array(
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
            'text'         => false,
            'font'         => 'courier',
            'fontsize'     => 10,
            'stretchtext'  => 0
        );

        $outer_border = array(
            'all' => array('width' => 0.1, 'cap' => 'round', 'join' => 'round', 'color' => array(50, 50, 50))
        );
        
        $inner_border_style = array('width' => 0.5, 'color' => array(229, 180, 163));
        $inner_border = array(
            'L' => $inner_border_style,
            'T' => $inner_border_style,
            'R' => $inner_border_style
        );

        $inner_border2 = array(
            'all' => array('width' => 0.5, 'color' => array(232, 230, 153))
        ); 

        $pdf->Rect(19, 18.5, 177, 61.4, 'D', $outer_border); // outline
        $pdf->Rect(19, 18.5, 135.5, 61.4, 'F', array(), $yellow);
        $pdf->Rect(20, 19.5, 133.5, 15.2, 'F', array(), $orange);
        $pdf->Rect(20.75, 20.25, 132, 6, 'F', array(), $white);
        $pdf->Rect(42.5, 35, 82, 44.8, 'DF', $inner_border, $white);

        // left box            
        $pdf->Rect(20.5, 41, 21.3, 7.2, 'F', false, $zebra_stripe[0]);
        $pdf->Rect(20.5, 48.2, 21.3, 7.2, 'F', false, $zebra_stripe[1]);
        $pdf->Rect(20.5, 55.4, 21.3, 7.2, 'F', false, $zebra_stripe[0]);
        $pdf->Rect(20.5, 62.6, 21.3, 7.2, 'F', false, $zebra_stripe[1]);
        $pdf->Rect(20.5, 69.8, 21.3, 7.2, 'F', false, $zebra_stripe[0]);
        $pdf->Rect(20.5, 41, 21.3, 36, 'D', $inner_border2, $white);

        // right box
        $pdf->Rect(124.8, 41, 28, 7.2, 'F', false, $zebra_stripe[0]);
        $pdf->Rect(124.8, 48.2, 28, 7.2, 'F', false, $zebra_stripe[1]);
        $pdf->Rect(124.8, 55.4, 28, 7.2, 'F', false, $zebra_stripe[0]);
        $pdf->Rect(124.8, 62.6, 28, 7.2, 'F', false, $zebra_stripe[1]);
        $pdf->Rect(138.8, 69.8, 14, 7.2, 'F', false, $zebra_stripe[0]);

        // right box outline
        $pdf->Line(138.8, 77, 152.8, 77, $box_outline);
        $pdf->Line(152.8, 77, 152.8, 41, $box_outline);
        $pdf->Line(152.8, 41, 124.8, 41, $box_outline);
        $pdf->Line(138.8, 77, 138.8, 69.8, $box_outline);
        $pdf->Line(124.8, 69.8, 138.8, 69.8, $box_outline);

        // vertical tear lines
        $pdf->Line(125.5, 19, 125.5, 80, $dotted_line_style);
        $pdf->Line(42.3, 19, 42.3, 80, $dotted_line_style);

        // header labels
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont($this->secondary_font, '', 6);
        $pdf->writeHTMLCell(30, 15, 23, 26.3, $html='<span style="letter-spacing:0.2mm">EVENT&nbsp;&nbsp;CODE</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true);
        $pdf->writeHTMLCell(30, 15, 45, 26.3, $html='<span style="letter-spacing:0.2mm">SEAT NO</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true);      
        $pdf->writeHTMLCell(30, 15, 107.2, 26.3, $html='<span style="letter-spacing:0.2mm">ADMISSION</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true);
        $pdf->writeHTMLCell(30, 15, 131, 26.3, $html='<span style="letter-spacing:0.2mm">EVENT&nbsp;&nbsp;CODE</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true);

        $pdf->SetTextColor($orange[0], $orange[1], $orange[2]);
        $pdf->SetFont($this->secondary_font, '', 12.5);
        $pdf->writeHTMLCell(10, 10, 21, 35, $html=str_replace('1.00', '', CRM_Utils_Money::format('1.00')), $border=0, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true);
        $pdf->SetTextColor(64, 64, 64);
        $pdf->SetFont($this->primary_font, '', 14);
        $pdf->writeHTMLCell(20, 10, 23, 35.5, $html='<span style="letter-spacing:0.1em;">' . $this->participant['participant_fee_amount'] . '</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='R', $autopadding=true);

        $pdf->SetFont($this->primary_font, '', 16);
        $pdf->SetTextColor(64, 64, 64);

        // header values
        $pdf->writeHTMLCell(50, 10, 21, 20.5, $html='<span>ESF0910</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true);
        $pdf->writeHTMLCell(20, 10, 41.3, 20.5, $this->getSeatNumber(218), $border=0, $ln=0, $fill=0, $reseth=true, $align='C', $autopadding=true);
 
        $pdf->SetFont($this->primary_font, '', 15);
        $pdf->writeHTMLCell(30, 10, 94, 20.5, $html='<span style="letter-spacing:0.8mm;">' . $this->participant['participant_fee_amount'] . '</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='R', $autopadding=true);
        $pdf->SetFont($this->primary_font, '', 19);
        $pdf->writeHTMLCell(50, 10, 127, 19.8, $html='<span>ESF0910</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true);

        // l/h box values
        $pdf->SetFont($this->primary_font, '', 14);
        $pdf->writeHTMLCell(20, 10, 21.8, 42.3, $html='<span style="letter-spacing:0.1em;">000432</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='C', $autopadding=true);
        $pdf->writeHTMLCell(20, 10, 21.8, 49.5, $html='<span style="letter-spacing:0.1em;">C00014</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='C', $autopadding=true);
        $pdf->SetFont($this->primary_font, '', 17);
        $pdf->writeHTMLCell(20, 10, 21.8, 56.5, $html='<span style="letter-spacing:0.1em;">R1</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='C', $autopadding=true);
        $pdf->writeHTMLCell(20, 10, 21.8, 63.7, $html='<span style="letter-spacing:0.1em;">1</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='C', $autopadding=true);
        $pdf->SetFont($this->primary_font, '', 13);
        $pdf->writeHTMLCell(23, 10, 20.5, 71.4, $html='<span style="letter-spacing:0.1em;">P03OCT13</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='C', $autopadding=true);

        // centre box values
        $pdf->SetFont($this->primary_font, '', 18);
        $pdf->writeHTMLCell(82, '', 42.5, 40, $html='<span style="line-height:0.77em">' . strtoupper($this->event['event_title']) . '</span>', $border=0, $ln=1, $fill=0, $reseth=true, $align='C', $autopadding=true);
        $pdf->SetFont($this->primary_font, '', 13);
        $pdf->writeHTMLCell(72, 30, 47.5, '', $html='<span style="line-height:0.75em">CIRCLE INTERACTIVE, CREATE CENTRE, SMEATON ROAD, BRISTOL, BS1 6XN</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='C', $autopadding=true);
        $pdf->SetFont($this->primary_font, '', 18);

        $pdf->writeHTMLCell(82, 20, 42.5, 69, $html='<span style="line-height:0.66em">' . strtoupper(date('D M d Y g:i A', strtotime($this->event['start_date']))) . '</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='C', $autopadding=true);


        $pdf->SetFont($this->primary_font, '', 13);
        $pdf->writeHTMLCell(50, 10, 127.5, 35, $html='<span style="letter-spacing:0.8mm;">CN 00014</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='', $autopadding=true);

        // r/h box values
        $pdf->SetFont($this->primary_font, '', 17);
        $pdf->writeHTMLCell(40, 10, 112.5, 42, $html='<span>000432</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='R', $autopadding=true);

        if (isset($this->payment_instrument_codes[$this->contribution['payment_instrument']])) {
            $payment_code = $this->payment_instrument_codes[$this->contribution['payment_instrument']];
        } else {
            $payment_code = 'OTR';
        }

        $pdf->writeHTMLCell(40, 10, 112.5, 49.2, $html='<span>' . $payment_code . '</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='R', $autopadding=true);
        $pdf->writeHTMLCell(40, 10, 112.5, 56.4, $html='<span>R1</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='R', $autopadding=true);

        $pdf->SetFont($this->primary_font, '', 14);
        $pdf->writeHTMLCell(40, 10, 112.5, 64, $html='<span>AM ' . $this->participant['participant_fee_amount'] . '</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='R', $autopadding=true);

        $pdf->SetFont($this->primary_font, '', 18);
        $pdf->writeHTMLCell(20, 10, 132.5, 70.5, $html='<span>' . $this->getSeatNumber($this->participant['id']) . '</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='R', $autopadding=true);

        // Footer (todo: move to printFooter)
        $pdf->SetFont($this->barcode_font, '', 11);
        $pdf->SetXY(155, 73.5);
        $pdf->StartTransform();
        $pdf->Rotate(90);
        $pdf->writeHTMLCell(49, 10, '', '', $html='<span style="letter-spacing:0.8mm;">123456789012</span>', $border=0, $ln=0, $fill=0, $reseth=true, $align='C', $autopadding=true);
        $pdf->StopTransform();

        $pdf->SetXY(159, 73.5);
        $pdf->StartTransform();
        $pdf->Rotate(90);
        $pdf->write1DBarcode('123456789012', 'C128A', '', '', 49, 12, 0.4, $barcode_style, 'N');
        $pdf->StopTransform();

        $pdf->SetXY(159, 0);
        $pdf->StartTransform();
        $pdf->Rotate(90);
        $pdf->Image(
            //dirname(__FILE__) . '/concert49.jpg',     // file
            implode(DIRECTORY_SEPARATOR, array(
                CRM_Core_Config::singleton()->extensionsDir,
                'uk.co.circleinteractive.module.tickets',
                'images',
                'logo2.png'
            )),
            0,               // x
            14,              // y
            50,              // w
            25,              // h
            '',              // type
            '',              // link
            '',              // align
            true,            // resize
            300,             // dpi
            $align,          // palign
            false,           // ismask
            false,           // imgmask
            $this->border,   // border
            true,            // fitbox
            false,           // hidden
            false,           // fitonpage
            false,           // alt
            array()          // altimgs
        );


    }

    public function printFooter() {
        // I am not here
    }
    
}




