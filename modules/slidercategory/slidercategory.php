<?php

// Exit if accessed directly or outside of PrestaShop context
if (!defined('_PS_VERSION_')) {
    exit;
}

// Define the Slidercategory module class
class Slidercategory extends Module
{
    // Module constructor: set basic module information
    public function __construct()
    {
        $this->name = 'slidercategory';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Perpetualcode';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('SliderCategory');
        $this->description = $this->l('Adds a horizontal slider to category pages.');

        // Define compatibility with PrestaShop versions
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Install the module and register required hooks
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook('header')
            && $this->registerHook('displayCategorySlider');
    }

    /**
     * Uninstall the module and clean up configuration
     */
    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('SLIDER_ENABLED');
    }

    /**
     * Configuration form handler in the back office
     */
    public function getContent()
    {
        // Process form submission
        if (Tools::isSubmit('submitSliderCategory')) {
            Configuration::updateValue('SLIDER_ENABLED', Tools::getValue('SLIDER_ENABLED'));
        }

        // Render form UI
        return $this->renderForm();
    }

    /**
     * Generate configuration form using HelperForm
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
        $helper->default_form_language = $this->context->language->id;

        $helper->fields_value['SLIDER_ENABLED'] = Configuration::get('SLIDER_ENABLED');

        return $helper->generateForm([[
            'form' => [
                'legend' => ['title' => $this->l('Settings')],
                'input' => [[
                    'type' => 'switch',
                    'label' => $this->l('Enable slider'),
                    'name' => 'SLIDER_ENABLED',
                    'is_bool' => true,
                    'values' => [
                        ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Enabled')],
                        ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Disabled')],
                    ]
                ]],
                'submit' => ['title' => $this->l('Save')],
            ]
        ]]);
    }

    /**
     * Load the module's CSS on all front pages
     */
    public function hookHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/custom.css');
    }

    /**
     * Hook to display the subcategory slider on category pages
     */
    public function hookDisplayCategorySlider($params)
    {
        // Get current category ID from URL
        $id_category = (int)Tools::getValue('id_category');
        if (!$id_category) {
            return '';
        }

        // Load current category and its direct subcategories
        $category = new Category($id_category, $this->context->language->id);
        $subcategories = $category->getSubCategories($this->context->language->id);

        // Add additional data to each subcategory (product count, image URL)
        foreach ($subcategories as &$subcat) {
            $cat = new Category($subcat['id_category'], $this->context->language->id);
            $subcat['product_count'] = $cat->getProducts($this->context->language->id, 0, 1, null, null, true);
            $subcat['image_url'] = $this->context->link->getCatImageLink($subcat['link_rewrite'], $subcat['id_image']);
        }

        // Send subcategories to template
        $this->context->smarty->assign([
            'subcategories' => $subcategories,
        ]);

        // Render the template file 
        return $this->display(__FILE__, 'views/templates/categorySlider.tpl');
    }
}
