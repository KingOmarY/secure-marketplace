// Pseudocode in post_product.php
$product_count = getProductCount($seller_id);
if ($product_count > 5) {
    $tax_rate = 10.00;
    updateUserTaxRate($seller_id, $tax_rate);
    logTaxChange($seller_id, $product_count, $tax_rate);
}