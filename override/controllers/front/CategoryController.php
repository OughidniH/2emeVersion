<?php

class CategoryController extends CategoryControllerCore
{
    public function initContent()
    {
        parent::initContent();

        $id_lang = (int) $this->context->language->id;
        $id_category = (int) Tools::getValue('id_category');
        $link = new Link();

        // SQL: Get product color combinations and associated images
        $sql = "
            SELECT
                p.id_product,
                pl.name AS product_name,
                pl.link_rewrite,
                pa.id_product_attribute,
                al.name AS color_name,
                pai.id_image
            FROM " . _DB_PREFIX_ . "product p
            INNER JOIN " . _DB_PREFIX_ . "product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = $id_lang)
            INNER JOIN " . _DB_PREFIX_ . "product_attribute pa ON pa.id_product = p.id_product
            INNER JOIN " . _DB_PREFIX_ . "product_attribute_combination pac ON pac.id_product_attribute = pa.id_product_attribute
            INNER JOIN " . _DB_PREFIX_ . "attribute a ON a.id_attribute = pac.id_attribute
            INNER JOIN " . _DB_PREFIX_ . "attribute_lang al ON (a.id_attribute = al.id_attribute AND al.id_lang = $id_lang)
            INNER JOIN " . _DB_PREFIX_ . "attribute_group ag ON (ag.id_attribute_group = a.id_attribute_group AND ag.is_color_group = 1)
            LEFT JOIN " . _DB_PREFIX_ . "product_attribute_image pai ON pai.id_product_attribute = pa.id_product_attribute
            INNER JOIN " . _DB_PREFIX_ . "category_product cp ON cp.id_product = p.id_product
            WHERE cp.id_category = $id_category
            GROUP BY pa.id_product_attribute
        ";

        $rows = Db::getInstance()->executeS($sql);
        $products = [];

        foreach ($rows as $row) {
            $id_product = (int) $row['id_product'];
            $id_product_attribute = (int) $row['id_product_attribute'];

            // Get price including tax
            $price = Product::getPriceStatic($id_product, true, $id_product_attribute);

            // Get image (fallback to cover)
            $id_image = $row['id_image'];
            if (!$id_image) {
                $cover = Product::getCover($id_product);
                if ($cover && isset($cover['id_image'])) {
                    $id_image = (int)$cover['id_image'];
                }
            }

            // Get size (non-color attribute)
            $sqlSize = "
                SELECT al.name
                FROM " . _DB_PREFIX_ . "product_attribute_combination pac
                INNER JOIN " . _DB_PREFIX_ . "attribute a ON a.id_attribute = pac.id_attribute
                INNER JOIN " . _DB_PREFIX_ . "attribute_group ag ON ag.id_attribute_group = a.id_attribute_group
                INNER JOIN " . _DB_PREFIX_ . "attribute_lang al ON al.id_attribute = a.id_attribute AND al.id_lang = $id_lang
                WHERE pac.id_product_attribute = $id_product_attribute
                AND ag.is_color_group = 0
            ";
            $size_name = Db::getInstance()->getValue($sqlSize);

            // Image URL
            $image_url = $link->getImageLink($row['link_rewrite'], $id_image, 'home_default');
            $image_large_url = $link->getImageLink($row['link_rewrite'], $id_image, 'large_default');

            // Final product structure
            $products[] = [
                'id_product' => $id_product,
                'id_product_attribute' => $id_product_attribute,
                'name' => $row['product_name'],
                'color_name' => $row['color_name'],
                'size_name' => $size_name,
                'canonical_url' => $link->getProductLink(
                    $id_product,
                    $row['link_rewrite'],
                    null, null,
                    $id_lang,
                    null,
                    $id_product_attribute
                ),
                'price' => $price,
                'regular_price' => $price,
                'has_discount' => false,
                'discount_type' => null,
                'discount_percentage' => null,
                'discount_amount_to_display' => null,
                'show_price' => true,
                'cover' => [
                    'bySize' => [
                        'home_default' => ['url' => $image_url],
                        'large_default' => ['url' => $image_large_url],
                    ],
                    'legend' => $row['product_name'] . ' - ' . $row['color_name']
                ],
                'flags' => [],
                'main_variants' => [],
            ];
        }

        // Replace products in category listing
        $listing = $this->context->smarty->getTemplateVars('listing');
        $listing['products'] = $products;
        $this->context->smarty->assign('listing', $listing);
    }
}
