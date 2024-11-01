<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Toret_Manager_Admin_Review_Details
{

    /**
     * Plugin slug
     *
     * @var string $toret_manager
     */
    protected string $toret_manager;

    /**
     * Constructor
     *
     * @param string $toret_manager
     */
    public function __construct(string $toret_manager)
    {
        $this->toret_manager = $toret_manager;
    }

}