<?php

 /**
 * Class to generate tickets in custom formats
 * based on CRM/Utils/PDF/Label.php
 * andyw@circle, 22/09/2012 - reworked for tickets 28/05/2013
 */

require_once implode(
    DIRECTORY_SEPARATOR,
    array(
        CRM_Core_Config::singleton()->extensionsDir,
        'uk.co.circleinteractive.module.tickets',
        'tcpdf',
        'tcpdf.php'
    )
);

class CRM_Utils_PDF_Ticket extends TCPDF {

    public $defaults;           // Default receipt format values
    public $format;             // Current receipt format values
    public $formatName;         // Name of format
    public $marginLeft;         // Left margin of receipt
    public $marginTop;          // Top margin of receipt
    public $marginRight;        // Right margin of receipt
    public $width;              // Width of receipt
    public $height;             // Height of receipt
    public $paddingLeft;        // Space between text and left edge of receipt
    public $paddingTop;         // Space between text and top edge of receipt
    public $fontSize;           // Character size (in points)
    public $metricDoc;          // Metric used for all PDF doc measurements
    public $fontName;           // Name of the font
    public $fontStyle;          // 'B' bold, 'I' italic, 'BI' bold+italic
    public $paperSize;          // Paper size name
    public $orientation;        // Paper orientation
    public $paper_dimensions;   // Paper dimensions array (w, h)
    
    /**
     * Constructor 
     *
     * @param $format   an array of receipt format values.
     * @param $unit     Unit of measure for the PDF document
     *
     * @access public
     */

    function __construct($format, $unit='mm') {
        
        $this->format = $format;
        
        $this->paperSize    = $this->getFormatValue('paper-size');
        $this->orientation  = $this->getFormatValue('orientation');

        parent::__construct($this->orientation, $unit, $this->paperSize);
        
        $this->setFormat($format, $unit);
        $this->generatorMethod = null;
        $this->SetFont($this->fontName, $this->fontStyle);
        $this->SetFontSize($this->fontSize);
        
        $this->SetMargins(
            $this->marginLeft, 
            $this->marginTop,
            $this->marginRight != $this->marginLeft ? $this->marginRight : -1
        );
                
        $this->SetAutoPageBreak(false);
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
    
    }
        
    function getFormatValue($name, $convert = false) {
        
        if (isset($this->format[$name])) {
            $value  = $this->format[$name];
            $metric = $this->format['metric'];
        } elseif (isset($this->defaults[$name])) {
            $value  = $this->defaults[$name];
            $metric = $this->defaults['metric'];
        } else {
            $metric = @$this->defaults['metric'];
        }
        if (isset($value)) {
            if ($convert) 
                $value = CRM_Utils_PDF_Utils::convertMetric($value, $metric, $this->metricDoc);
        } else {
            $value = '';
        }
        return $value;
    }
       
    /*
     * Generator function
     */
    function generateTicket($text) {
        
        $args = array(
            'w'           => $this->width, 
            'h'           => 0, 
            'txt'         => $text, 
            'border'      => 0, 
            'align'       => 'L', 
            'fill'        => 0, 
            'ln'          => 0, 
            'x'           => '', 
            'y'           => '', 
            'reseth'      => true, 
            'stretch'     => 0, 
            'ishtml'      => false, 
            'autopadding' => false, 
            'maxh'        => $this->height
        );
        
        /*
        if ( $args['ishtml'] == true ) {
            $this->writeHTMLCell( $args['w'], $args['h'],
                                  $args['x'], $args['y'],
                                  $args['txt'], $args['border'],
                                  $args['ln'], $args['fill'],
                                  $args['reseth'], $args['align'],
                                  $args['autopadding']);        	
        } else {
            $this->multiCell( $args['w'], $args['h'],
                              $args['txt'], $args['border'],
                              $args['align'], $args['fill'],
                              $args['ln'], $args['x'],
                              $args['y'], $args['reseth'],
                              $args['stretch'], $args['ishtml'],
                              $args['autopadding'], $args['maxh'] );
        }
        */
        
    }
 
    /*
     * function to Print a label
     */
    function AddPdfLabel($text) {
        
        $posX = $this->marginLeft;
        $posY = $this->marginTop;
        
        $this->SetXY($posX + $this->paddingLeft, $posY + $this->paddingTop);
        if ($this->generatorMethod) {
            call_user_func_array(
                array($this->generatorObject, $this->generatorMethod),
                array($text)
            );
        } else {  
            $this->generateTicket($text);
        }
        /*
        if ($this->countY == $this->yNumber) {
            // End of column reached, we start a new one
            $this->countX++;
            $this->countY=0;
        }
        
        if ($this->countX == $this->xNumber) {
            // Page full, we start a new one
            $this->countX=0;
            $this->countY=0;
        }
        
        // We are in a new page, then we must add a page
        if (($this->countX ==0) and ($this->countY==0)) {
            $this->AddPage();
        }
        */
    }
    
    function getFontNames() {
        // Define labels for TCPDF core fonts
        $fontLabel = array(
            'courier'    => ts('Courier'),
            'helvetica'  => ts('Helvetica'),
            'times'      => ts('Times New Roman'),
            'dejavusans' => ts('Deja Vu Sans (UTF-8)')
        );
        $tcpdfFonts = $this->fontlist;
        foreach ($tcpdfFonts as $fontName) {
            if (array_key_exists($fontName, $fontLabel)) {
                $list[$fontName] = $fontLabel[$fontName];
            }
        }
        return $list;
    }
    
    // Initialize receipt format settings 
    function setFormat(&$format, $unit) {
                
        $this->defaults     = CRM_Core_BAO_PdfFormat::getDefaultValues();
        
        $this->format       = &$format;
        $this->formatName   = $this->getFormatValue('name');
        $this->paperSize    = $this->getFormatValue('paper-size');
        $this->orientation  = $this->getFormatValue('orientation');
        $this->fontName     = $this->getFormatValue('font-name');
        $this->fontSize     = $this->getFormatValue('font-size');
        $this->fontStyle    = $this->getFormatValue('font-style');
        $this->metricDoc    = $unit;
        $this->marginLeft   = $this->getFormatValue('margin-left', true);
        $this->marginTop    = $this->getFormatValue('margin-top', true);
        $this->marginRight  = $this->getFormatValue('margin-right', true);
        $this->width        = $this->getFormatValue('width', true);
        $this->height       = $this->getFormatValue('height', true);
        $this->paddingLeft  = $this->getFormatValue('padding-left',true);
        $this->paddingTop   = $this->getFormatValue('padding-top',true);
        $this->paddingRight = $this->getFormatValue('padding-right', true);
        
        $paperSize = CRM_Core_BAO_PaperSize::getByName($this->paperSize);
        $width     = CRM_Utils_PDF_Utils::convertMetric($paperSize['width'],  $paperSize['metric'], $this->metricDoc);
        $height    = CRM_Utils_PDF_Utils::convertMetric($paperSize['height'], $paperSize['metric'], $this->metricDoc);
        
        $this->paper_dimensions = array($width, $height);
    }
    
    function setGenerator($objectinstance, $methodname='generateTicket') {
        $this->generatorMethod = $methodname;
        $this->generatorObject = $objectinstance; 
    }
    
}


