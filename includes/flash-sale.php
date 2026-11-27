<?php
declare(strict_types=1);
if (!defined('PLASMART_APP_CONTEXT')) { http_response_code(403); exit; }

function flash_timestamp(string $value): int {
    if ($value === '') return 0;
    $time = strtotime($value);
    return $time === false ? 0 : $time;
}
function flash_state(?array $flash = null, ?int $now = null): string {
    $flash ??= flash_sale(); $now ??= time();
    if (empty($flash['active']) || empty($flash['products'])) return 'idle';
    $end = flash_timestamp((string)($flash['ends_at'] ?? ''));
    $start = flash_timestamp((string)($flash['starts_at'] ?? ''));
    if (!$end) return 'idle';
    if ($end <= $now) return 'ended';
    if ($start && $start > $now) return 'upcoming';
    return 'live';
}
function flash_offer(array $product, bool $includeSoldOut = false): ?array {
    $flash = flash_sale(); $slug = (string)$product['slug'];
    if (flash_state($flash) !== 'live' || !in_array($slug, $flash['products'] ?? [], true)) return null;
    $offer = $flash['offers'][$slug] ?? null;
    if (!is_array($offer) || !isset($offer['price'], $offer['total_stock'], $offer['remaining_stock'])) return null;
    if ((int)$offer['total_stock'] < 1 || (int)$offer['price'] <= 0 || (int)$offer['price'] > product_regular_price($product) || (!$includeSoldOut && (int)$offer['remaining_stock'] < 1)) return null;
    return $offer;
}
function product_regular_price(array $product): int {
    return !empty($product['variants']) ? (int)min(array_column($product['variants'], 'price')) : (int)$product['price'];
}
function product_price_quote(array $product,?int $variant=null,int $qty=1):array {
 $qty=max(1,$qty);$row=!empty($product['variants'])?($product['variants'][$variant??0]??null):null;
 $normal=$row?(int)$row['price']:product_regular_price($product);$price=$normal;$source='normal';
 $offer=flash_offer($product);$flashPrice=null;
 if($offer&&product_regular_price($product)>0){$flashPrice=(int)round($normal*(int)$offer['price']/product_regular_price($product));if($flashPrice<$price){$price=$flashPrice;$source='flash';}}
 $rule=$row?($row['wholesale']??null):($product['wholesale']??null);
 if(is_array($rule)&&$qty>=(int)($rule['min_qty']??PHP_INT_MAX)&&(int)($rule['price']??0)>0&&(int)$rule['price']<$price){$price=(int)$rule['price'];$source='wholesale';}
 return ['price'=>$price,'regular'=>$normal,'old'=>$source==='flash'?$normal:null,'source'=>$source,'subtotal'=>$price*$qty];
}
function product_current_price(array $product,?int $variant=null,int $qty=1):int {return product_price_quote($product,$variant,$qty)['price'];}
function product_price_text(array $product): string {
    return ((!empty($product['variants']) && !flash_offer($product)) ? 'Mulai ' : '').money(product_current_price($product));
}
