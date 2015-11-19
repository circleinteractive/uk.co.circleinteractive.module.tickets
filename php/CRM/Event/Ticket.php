<?php

/* 
 * Event Tickets Extension for CiviCRM - Circle Interactive 2013
 * Author: andyw@circle
 *
 * Distributed under the GNU Affero General Public License, version 3
 * http://www.gnu.org/licenses/agpl-3.0.html 
 */

abstract class CRM_Event_Ticket {
    
    // Force extending classes to provide a human-readable name - ie: return ts('Name of template');
    abstract public function name();
    
    public $pdf;

    protected $format,
              $fonts_dir,
              $image_dir,
              $ext_dir,
              $header_height,
              $debug, 
              $border;
    
    protected $currentY;
    
    // entities we may need for building receipt
    protected $contact,
              $contribution,
              $event,
              $membership,
              $participant;
    
    protected $additional_participants_same_person;         
    
    public function __construct() {
        
        require_once 'api/api.php';
        $config = &CRM_Core_Config::singleton();
        
        $this->ext_dir   = $config->extensionsDir . DIRECTORY_SEPARATOR . 'uk.co.circleinteractive.module.tickets'; 
        $this->fonts_dir = $this->ext_dir . DIRECTORY_SEPARATOR . 'fonts';
        $this->image_dir = $config->customFileUploadDir;
        $this->border    = '';
       
        // set some basic defaults if $format not populated
        $this->format = array_merge(
            array(
                'paper-size'    => 'A4',
                'metric'        => 'mm',
                'margin-left'   => 5,
                'margin-top'    => 5,
                'margin-bottom' => 5,
                'margin-right'  => 5,
                'font-size'     => 10,
                'orientation'   => 'L'
            ),
            $this->format
        );

        $this->additional_participants_same_person = 0;

    }
    
    // Build address string from a passed in array
    protected function buildAddress($address) {
        
        $output = array();
        
        if (isset($address['country_id']) and !empty($address['country_id'])) 
            $address['country'] = CRM_Core_PseudoConstant::country($address['country_id']);  
        
        if (isset($address['state_province_id']) and !empty($address['state_province_id'])) 
            $address['state_province'] = CRM_Core_PseudoConstant::stateProvince($address['state_province_id']);  
        
        foreach (
            array(
                'street_address',
                'supplemental_address_1',
                'supplemental_address_2',
                'city',
                'postal_code',
                'country'
            ) as $field)
                if (isset($address[$field]) and !empty($address[$field]))
                    $output[] = $address[$field];
        
        return implode("\n", $output);
    }
    
    final public function create(&$params, $is_reprint = false) {
        
        static $batch_count = 0;

        $pdf = &$this->pdf;

        $rows = isset($this->format['multiple']['rows']) ? $this->format['multiple']['rows'] : 1;
        $cols = isset($this->format['multiple']['cols']) ? $this->format['multiple']['cols'] : 1;

        $num_per_page = $rows * $cols;

        // if single or first of a batch, initialize pdf object
        if (!$batch_count or $is_reprint) {

            // initialize tcpdf instance ..        
            $pdf = new CRM_Utils_PDF_Ticket($this->format, $this->format['metric']);

            // set basic defaults
            $pdf->open();
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }

        $this->preview = isset($params['preview']) and !empty($params['preview']) ? $params['preview'] : false;
        $this->inline  = (isset($params['output']) and $params['output'] == 'inline');
        watchdog('andyw', 'ticket params = <pre>' . print_r($params, true) . '</pre>');
        if (isset($params['additional_participants_same_person']))
            $this->additional_participants_same_person = $params['additional_participants_same_person'];
                
        $page_height = $pdf->getPageHeight();
        $page_width  = $pdf->getPageWidth();

        //$page_height = $dimensions['hk'] - $dimensions['tm'] - $dimensions['bm'];
        //$page_width  = $dimensions['wk'] - $dimensions['lm'] - $dimensions['rm'];
        
        if (!($batch_count % $num_per_page) or $is_reprint or $this->preview) {

            $pdf->AddPage();
            $pdf->top  = 0;
            $pdf->left = 0;
        
        } else {
            
            if (!($batch_count % $cols)) {
                $pdf->top += ((($page_height/* - $this->format['margin-top'] - $this->format['margin-bottom']*/) / ($rows - 1)) * 1.35);
                watchdog('andyw', 'pdf->top = ' . $pdf->top);
            } else
                $pdf->left += ((($page_width/* - $this->format['margin-left'] - $this->format['margin-right']*/) / ($cols - 1)) * 1.35);
        
        }

        $pdf->addTTFfont($this->fonts_dir . DIRECTORY_SEPARATOR . 'SourceSansPro-Regular.ttf', '', '', 32);
        $pdf->addTTFfont($this->fonts_dir . DIRECTORY_SEPARATOR . 'SourceSansPro-Semibold.ttf', '', '', 32);
        
        if (!isset($this->fontSize))
            $this->fontSize = 10;

        $pdf->SetFont('sourcesanspro', '', $this->fontSize, true);
        $pdf->SetGenerator($this, "generateTicket");
        
        // now call 'initialize', allowing custom ticket templates to override
        // the above defaults if they wish to
        $this->initialize();
        
        // Are we for real?
        if (!$this->preview) {

            // retrieve the required entities
            $this->loadEntity('participant', $params['participant_id']);
            $this->loadEntity('event', $params['event_id']);
            $this->loadEntity('contact', $this->participant['contact_id']);

            if ($contribution_id = CRM_Core_DAO::singleValueQuery("
                SELECT contribution_id FROM civicrm_participant_payment
                WHERE participant_id = %1
            ", array(
                  1 => array($params['participant_id'], 'Positive')
               )
            )) {
                $this->loadEntity('contribution', $contribution_id);
            } else {
                $this->contribution = array();
            }

            // Retrieve event address using loc_block_id
            if (isset($this->event['loc_block_id']) and $this->event['loc_block_id'] and $address_id = CRM_Core_DAO::singleValueQuery("
                SELECT address_id FROM civicrm_loc_block WHERE id = %1
            ", array(
                  1 => array($this->event['loc_block_id'], 'Positive')
               )
            )) {
                $result = civicrm_api('address', 'get', array(
                    'version' => 3,
                    'id'      => $address_id
                ));
                if (!$result['is_error'])
                    $this->event['address'] = reset($result['values']);                 
            
            }
                    
        } else {
            
            // Otherwise (if this is a preview), populate with dummy details
            $this->participant        = $this->getDummyDetails('participant');
            $this->contact            = $this->getDummyDetails('contact');
            $this->contribution       = $this->getDummyDetails('contribution');
            $this->event              = $this->getDummyDetails('event');
        
        }
        
        // run main ticket generation code
        $this->generateTicket($params);
        $batch_count++;
        
    }
    
    public function generateTicket(&$params) {
        
        $this->printHeader();
        $this->printBody();
        $this->printFooter();
        
    }
    
    protected function getDummyDetails($entity) {
        $details = array(
            'address'      => array(
                'street_address'    => '39 Bar Avenue',
                'city'              => 'Bazville',
                'postal_code'       => 'BZ1 1AB',
                'state_province_id' => 2620,
                'country_id'        => 1226

            ),
            'participant'  => array(
                'participant_register_date' => date('c')
            ),
            'contact'      => array(
                'contact_type'   => 'Individual',
                'display_name'   => 'Bob Foo'
            ),
            'contribution' => array(
                'invoice_id'             => md5('x'),
                'is_pay_later'           => 0,
                'total_amount'           => 1,
                'contribution_status_id' => 1,
                'receive_date'           => date('c')
            ),
            'event'        => array(
                'title'    => 'Test Event - this may be a longer title which wraps onto more than one line', 
                'location' => array(
                    'Address Line 1',
                    'Address Line 2',
                    'Town / City',
                    'Postal Code'
                )
            )
        );
        return isset($details[$entity]) ? $details[$entity] : array();
    }

    protected function getLogo() {
        
        $ds = DIRECTORY_SEPARATOR;
        
        // Check if a logo.xxx file exists in files/civicrm/custom, or default
        // to the Civi logo included with the extension
        switch (true) {
            case file_exists($file = $this->image_dir . $ds . 'logo.jpg'):
            case file_exists($file = $this->image_dir . $ds . 'logo.gif'):            
            case file_exists($file = $this->image_dir . $ds . 'logo.png'):
            case file_exists($file = $this->ext_dir . $ds . 'images' . $ds . 'civicrm-logo.png'):
                break;
            default:
                return new Stdclass;
        }
        
        $meta = @getimagesize($file) or $meta = array(0, 0);        
        
        return (object)array(
            'file'   => $file,
            'width'  => $meta[0],
            'height' => $meta[1]
        );
        
    }
    
    public function initialize() { 
        // This method is intentionally left empty so child classes can override it if necessary.
        // Do not remove it.
    }

    public function loadEntity($entity, $params) {
        
        if (is_array($params))
            $params += array('version' => 3);
        else
            $params = array(
                'version' => 3,
                'id'      => $params
            );

        $result = civicrm_api($entity, 'get', $params);
        if (!$result['is_error']) {
            $this->$entity = reset($result['values']);
            return true;
        }
        $this->$entity = array();
        return false;

    }
    
    // page callback for ticket preview - output pdf data inline
    public function preview() {
        
        $template_class = $_GET['template'];

        try {  
            $ticket = new $template_class;
        } catch (Exception $e) {
            echo "Unable to instantiate template class '$template_class'";
            CRM_Utils_System::civiExit();
        }
     
        $params = array(
            'filename' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(microtime(true)) . '.pdf', 
            'preview'  => true,
            'event_id' => $_GET['id']
        );

//        $ticket->pdf->top  = 0;
//        $ticket->pdf->left = 0;

        $ticket->create($params);
        $ticket->pdf->Output('preview.pdf', 'I');
        
        CRM_Utils_System::civiExit();
    
    }
    
    // 'Print' methods to generate various portions of a ticket
    // These are the primary methods to override when creating custom receipt templates
    
    public function printHeader() {
        $pdf = &$this->pdf;
        $this->printLogo();
        $pdf->Line(0, 24, 139, 24, array('width' => 0.2, 'cap' => 'round', 'join' => 'round', 'color' => array(150, 150, 150)));               
    }
    
    public function printBody() {
        
        $pdf = &$this->pdf;
        $pdf->SetXY(55, 30);
        $pdf->SetFont('sourcesansprosemib', '', $pdf->fontSize + 4);
        $pdf->MultiCell(84, 0, $this->event['title'], $pdf->border, 'C', false, 1, '', '', true, 0, false, true, 0, 'M');
        $pdf->SetFont('sourcesanspro', '', $pdf->fontSize + 2);

        if ($this->event['location'] or $this->event['start_time']) {
            $pdf->SetXY(55, $pdf->GetY() + 2);
            $pdf->MultiCell(84, 0, 'at', $this->border, 'C', false, 1, '', '', true, 0, false, true, 0, 'M');
            if ($this->event['location']) {
               $pdf->SetXY(55, $pdf->GetY() + 1);
               $pdf->SetFont('sourcesansprosemib', '', $pdf->fontSize + 2);
               $pdf->MultiCell(84, 0, implode(', ', $this->event['location']), $this->border, 'C', false, 1, '', '', true, 0, false, true, 0, 'M'); 
            }
        }

    }
    
    public function printFooter() {
    
    }
    
    public function printHeaderTop() {
        
        // Print organization details and logo
        //$this->printOrganizationDetails();
        //$this->printLogo();
    
    }
    
    public function printLogo($width=0, $height=0, $align='L') {
        
        $pdf  = &$this->pdf;
        $logo = $this->getLogo();
        
        if (!$height and $this->header_height)
            $height = $this->header_height;
        elseif(!$height)
            $height = 15;

        if (!$width)
            $width = 65;

        $pdf->Image(
            $logo->file,     // file
            '',              // x
            $pdf->marginTop, // y
            $width,          // w
            $height,         // h
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
    
    public function printOrganizationDetails() {
        
        $pdf          = &$this->pdf;
        $organization = $this->getOrganizationDetails();
        $address      = &$organization['domain_address'];
        $details      = array();
            
        // Assemble organization details
        $details  = $organization['name'];
        $details .= "\n" . $this->buildAddress($address);
        
        // get the height when printed - this determines max height of the logo image
        $this->header_height = $pdf->getStringHeight(0, $details);

        // Print the domain organization name and address       
		$pdf->MultiCell(50, 20, $details, $this->border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M');
    
    }
    
    protected function setDebug($debug = true) {
        if (!$debug) {
            $this->debug  = false;
            $this->border = 0;
        } else {
            $this->debug  = true;
            $this->border = "LTRB";
        }
    }
    
    
};

?>