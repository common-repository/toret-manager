<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Disabled and specific term types
 */
define('TORET_MANAGER_DISABLED_TERM_TYPES', array(
        'post_format',
        'product_shipping_class',
    )
);
define('TORET_MANAGER_SPECIFIC_TERM_TYPES', array());

/**
 * Types without parent
 */
define('TORET_MANAGER_TYPES_WO_PARENT', array(
        'product_tag',
        'post_tag',
        'post',
        'user',
    )
);

/**
 * WooCommerce types
 */
define('TORET_MANAGER_WOO_TYPES', array(
        'product_cat',
        'product_tag',
        'shop_order',
        'product',
        'order',
    )
);

/**
 * Sync constants
 */
define('TORET_MANAGER_NOTIFY_COUNTER', 'trman_notification_count');
define('TORET_MANAGER_SYNC_CRON', 'wp_trman_initial_sync_cron');

/**
 * Allowed post statuses
 */
define('TORET_MANAGER_ALLOWED_POST_STATUSES', array('publish', 'draft', 'pending', 'future', "private", 'inherit', 'trash'));

/**
 * Disabled post types
 */
define('TORET_MANAGER_DISABLED_POST_TYPES', array(
        'attachment',
        'revision',
        'nav_menu_item',
        'custom_css',
        'customize_changeset',
        'oembed_cache',
        'user_request',
        'wp_block',
        'wp_template',
        'wp_template_part',
        'wp_global_styles',
        'wp_navigation',
        'product_variation',
        'shop_order_refund',
        'shop_coupon',
        'patterns_ai_data',
    )
);
define('TORET_MANAGER_SPECIFIC_POST_TYPES', array(
        'product',
        'shop_order',
    )
);

/**
 * API endpoints
 */
define('TORET_MANAGER_API_ENDPOINTS', array(
        'product' => 'Product',
        'order' => 'Order',
        'shop_order' => 'Order',
        'category' => 'Category',
        'post_tag' => 'Category',
        'user' => 'Customer',
        'post' => 'Post',
        'product_cat' => 'Category',
        'product_tag' => 'Category',
        'product_attribute' => 'Category',
        'comment' => 'Comment',
        'review' => 'Comment',
        'order_note' => 'Comment',
        'stock' => 'Product',
        'anypost' => 'Post',
        'term' => 'Category',
    )
);

/**
 * Product items
 */
define('TORET_MANAGER_PRODUCT_DATA_MANDATORY', array(
        'productID' => 'x;x',
        'parentID' => 'x;0',
        'parentInternalID' => 'x;x',
        'productType' => 'x;0',
        'postStatus' => 'x;0',
        'title' => 'x;0',
        'price' => 'x;0',
        'priceVat' => 'x;0',
        'currency' => 'x;0',
        'vat' => 'x;0',
        'stockStatus' => 'x;0',
        'shortDescription' => 'x;0',
        'description' => 'x;0',
        'manageStock' => 'x;0',
        'url' => 'x;x',
        'editUrl' => 'x;x',
        'meta' => 'x;0',
        'wooUniqueID' => 'x;0',
        'isSticky' => 'x;0',
    )
);
define('TORET_MANAGER_PRODUCT_DATA', array_merge(
        TORET_MANAGER_PRODUCT_DATA_MANDATORY,
        array(
            'sku' => '0;0',
            'salePrice' => '0;0',
            'salePriceVat' => '0;0',
            'saleFromDate' => '0;0',
            'saleToDate' => '0;0',
            'totalSales' => '0;0',
            'taxStatus' => '0;0',
            'taxClass' => '0;0',
            'shippingClass' => '0;0',
            'backorders' => '0;0',
            'backordersNotify' => '0;0',
            'lowStockAmount' => '0;0',
            'soldIndividually' => '0;0',
            'virtual' => '0;0',
            'downloadable' => '0;0',
            'files' => '0;0',
            'downloadLimit' => '0;0',
            'downloadExpiry' => '0;0',
            'productImageGallery' => '0;0',
            'thumbnail' => '0;0',
            'allowReview' => '0;0',
            'averageRating' => '0;0',
            'ratingCount' => '0;x',
            'reviewCount' => '0;0',
            'visibility' => '0;0',
            'ean' => '0;0',
            'isbn' => '0;0',
            'gtin' => '0;0',
            'weight' => '0;0',
            'length' => '0;0',
            'width' => '0;0',
            'height' => '0;0',
            'weightUnit' => '0;x',
            'dimensionUnit' => '0;x',
            'category' => '0;0',
            'attributes' => 'x;0',
            'tags' => '0;0',
            'purchaseNote' => '0;0',
            'menuOrder' => '0;0',
            'crossSell' => '0;0',
            'crossSellInternal' => '0;x',
            'upSell' => '0;0',
            'upSellInternal' => '0;x',
            'reviewInternalID' => '0;0',
            'grouped' => '0;0',
            'productUrl' => '0;0',
            'buttonText' => '0;0',
            'groupedInternal' => '0;x',
        )
    )
);

/**
 * Category items
 */
define('TORET_MANAGER_CATEGORY_DATA_MANDATORY', array(
        'internalID' => 'x;x',
        'categoryID' => 'x;0',
        'parentID' => 'x;0',
        'parentInternalID' => 'x;x',
        'title' => 'x;0',
        'type' => 'x;0',
        'slug' => 'x;0',
        'editUrl' => 'x;x',
        'meta' => 'x;0',
    )
);
define('TORET_MANAGER_CATEGORY_DATA', array_merge(
        TORET_MANAGER_CATEGORY_DATA_MANDATORY,
        array(
            'description' => '0;0',
            'thumbnail' => '0;0',
            'taxonomyInternalID' => 'x;x',
        )
    )
);

/**
 * Order items
 */
define('TORET_MANAGER_ORDER_DATA_MANDATORY', array(
        'internalID' => 'x;x',
        'parentID' => 'x;0',
        'parentInternalID' => 'x;x',
        'orderID' => 'x;x',
        'orderStatus' => 'x;0',
        'billingEmail' => 'x;0',
        'editUrl' => 'x;x',
        'meta' => 'x;0',
    )
);
define('TORET_MANAGER_ORDER_DATA', array_merge(
        TORET_MANAGER_ORDER_DATA_MANDATORY,
        array(
            'orderTitle' => '0;0',
            'orderCommentStatus' => '0;x',
            'orderPassword' => '0;x',
            'orderName' => '0;x',
            'parentInternalID' => '0;x',
            'orderCommentsCount' => '0;x',
            'orderCustomerID' => '0;0',
            'customerInternalID' => '0;x',
            'orderDownloadPermission' => '0;0',
            'orderStockReduced' => '0;0',
            'orderBillingCountry' => '0;0',
            'orderShippingCountry' => '0;0',
            'orderCurrency' => '0;0',
            'orderCartDiscount' => '0;0',
            'orderCartDiscountTax' => '0;0',
            'orderShipping' => '0;0',
            'orderShippingTax' => '0;0',
            'orderTax' => '0;0',
            'orderTotal' => '0;0',
            'priceIncludedTax' => '0;0',
            'billingAddressIndex' => '0;x',
            'shippingAddressIndex' => '0;x',
            'billingFirstName' => '0;0',
            'billingLastName' => '0;0',
            'billingAddress' => '0;0',
            'billingAddress2' => '0;0',
            'billingCity' => '0;0',
            'billingZip' => '0;0',
            'billingCountry' => '0;0',
            'billingPhone' => '0;0',
            'shippingFirstName' => '0;0',
            'shippingLastName' => '0;0',
            'shippingAddress' => '0;0',
            'shippingAddress2' => '0;0',
            'shippingCity' => '0;0',
            'shippingZip' => '0;0',
            'shippingCountry' => '0;0',
            'shippingEmail' => '0;0',
            'shippingPhone' => '0;0',
            'paymentMethod' => '0;0',
            'paymentMethodTitle' => '0;0',
            'shippingMethod' => '0;0',
            'usedCoupons' => '0;0',
            'items' => '0;0',
            'fees' => '0;0',
            'orderCreatedDate' => '0;0',
            'orderEditedDate' => '0;0',
            'orderComments' => '0;0',
            'customerNote' => '0;0',
            'weight' => '0;0',
        )
    )
);

/**
 * Customer items
 */
define('TORET_MANAGER_USER_DATA_MANDATORY', array(
        'userID' => 'x;x',
        'userLogin' => 'x;0',
        'userEmail' => 'x;0',
        'capabilities' => 'x;0',
        'editUrl' => 'x;x',
        'meta' => 'x;0',
    )
);
define('TORET_MANAGER_USER_DATA', array_merge(
        TORET_MANAGER_USER_DATA_MANDATORY,
        array(
            'userNiceName' => '0;0',
            'userFirstName' => '0;0',
            'userLastName' => '0;0',
            'userDisplayName' => '0;0',
            'userUrl' => '0;x',
            'userRegistered' => '0;0',
            'description' => '0;0',
            'locale' => '0;0',
            'billingFirstName' => '0;0',
            'billingLastName' => '0;0',
            'billingAddress' => '0;0',
            'billingAddress2' => '0;0',
            'billingCity' => '0;0',
            'billingZip' => '0;0',
            'billingCountry' => '0;0',
            'billingEmail' => '0;0',
            'billingPhone' => '0;0',
            'shippingFirstName' => '0;0',
            'shippingLastName' => '0;0',
            'shippingAddress' => '0;0',
            'shippingAddress2' => '0;0',
            'shippingCity' => '0;0',
            'shippingZip' => '0;0',
            'shippingCountry' => '0;0',
            'shippingEmail' => '0;0',
            'shippingPhone' => '0;0',
        )
    )
);

/**
 * Review items
 */
define('TORET_MANAGER_REVIEW_DATA_MANDATORY', array(
        'commentID' => 'x;x',
        'postID' => 'x;0',
        'postInternalID' => 'x;x',
        'parentID' => 'x;0',
        'parentInternalID' => 'x;x',
        'commentAuthor' => 'x;x',
        'commentAuthorEmail' => 'x;0',
        'commentContent' => 'x;0',
        'commentType' => 'x;0',
        'editUrl' => 'x;x',
        'meta' => 'x;0',
    )
);
define('TORET_MANAGER_REVIEW_DATA', array_merge(
        TORET_MANAGER_REVIEW_DATA_MANDATORY,
        array(
            'commentAuthorUrl' => '0;0',
            'authorInternalID' => '0;x',
            'commentDate' => '0;0',
            'commentApproved' => '0;0',
            'commentUserID' => '0;0',
            'productRating' => '0;0',
            'verified' => '0;0',
        )
    )
);

/**
 * Post items
 */
define('TORET_MANAGER_POST_DATA_MANDATORY', array(
        'postID' => 'x;x',
        'authorID' => 'x;0',
        'authorInternalID' => 'x;x',
        'title' => 'x;0',
        'postStatus' => 'x;0',
        'postType' => 'x;0',
        'editUrl' => 'x;x',
        'meta' => 'x;0',
        'isSticky' => 'x;0',
    )
);
define('TORET_MANAGER_POST_DATA', array_merge(
        TORET_MANAGER_POST_DATA_MANDATORY,
        array(
            'parentID' => '0;0',
            'parentInternalID' => '0;x',
            'excerpt' => '0;0',
            'content' => '0;0',
            'thumbnail' => '0;0',
            'url' => '0;x',
            'commentStatus' => '0;0',
            'commentCount' => '0;0',
            'postPassword' => '0;0',
            'createdDate' => '0;0',
            'editedDate' => '0;0',
            'category' => '0;0',
            'tags' => '0;0',
            'commentsInternalID' => '0;0',
            'menuOrder' => '0;0',
        )
    )
);

/**
 * Stock items
 */
define('TORET_MANAGER_STOCK_DATA_MANDATORY', array(
        'trman_module_upload_stock_qty' => '0;0',
        'trman_module_download_stock_qty' => '0;0',
    )
);
define('TORET_MANAGER_STOCK_DATA', array());