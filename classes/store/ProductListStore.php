<?php namespace Lovata\Shopaholic\Classes\Store;

use Kharanenka\Helper\CCache;
use Lovata\Shopaholic\Classes\Item\CategoryItem;
use Lovata\Shopaholic\Models\Offer;
use Lovata\Shopaholic\Models\Product;
use Lovata\Shopaholic\Models\Settings;
use Lovata\Shopaholic\Plugin;
use System\Classes\PluginManager;

/**
 * Class ProductListStore
 * @package Lovata\Shopaholic\Classes\Store
 * @author Andrey Kharanenka, a.khoronenko@lovata.com, LOVATA Group
 */
class ProductListStore
{
    const CACHE_TAG_LIST = 'shopaholic-product-list';

    const SORT_NO = 'no';
    const SORT_PRICE_ASC = 'price|asc';
    const SORT_PRICE_DESC = 'price|desc';
    const SORT_NEW = 'new';
    const SORT_POPULARITY_DESC = 'popularity|desc';

    /**
     * Get available sorting value list
     * @return array
     */
    public function getAvailableSorting()
    {
        return [
            self::SORT_NO,
            self::SORT_PRICE_ASC,
            self::SORT_PRICE_DESC,
            self::SORT_NEW,
            self::SORT_POPULARITY_DESC,
        ];
    }

    /**
     * Get product ID list by sorting value
     * @param string $sSorting
     * @return array
     */
    public function getBySorting($sSorting)
    {
        //Get cache data
        $arCacheTags = [Plugin::CACHE_TAG, self::CACHE_TAG_LIST];
        $sCacheKey = $sSorting;

        $arProductIDList = CCache::get($arCacheTags, $sCacheKey);
        if(!empty($arProductIDList)) {
            return $arProductIDList;
        }

        return $this->updateCacheBySorting($sSorting);
    }

    /**
     * Update cache product ID list by sorting and get new
     * @param string $sSorting
     * @return array|null
     */
    public function updateCacheBySorting($sSorting)
    {
        //Get cache data
        $arCacheTags = [Plugin::CACHE_TAG, self::CACHE_TAG_LIST];
        $sCacheKey = $sSorting;

        switch($sSorting) {
            case self::SORT_PRICE_ASC :

                //Get product ID list (sort by offer price)
                //We can not use groupBy() in this place
                /** @var array $arProductIDList */
                $arProductIDList = Offer::active()->orderBy('price', 'asc')->lists('product_id');
                if(empty($arProductIDList)) {
                    return null;
                }

                $arProductIDList = array_unique($arProductIDList);

                break;
            case self::SORT_PRICE_DESC :

                //Get product ID list (sort by offer price)
                //We can not use groupBy() in this place
                /** @var array $arProductIDList */
                $arProductIDList = Offer::active()->orderBy('price', 'desc')->lists('product_id');
                if(empty($arProductIDList)) {
                    return null;
                }

                $arProductIDList = array_unique($arProductIDList);

                break;
            case self::SORT_POPULARITY_DESC :

                if(!PluginManager::instance()->hasPlugin('Lovata.PopularityShopaholic')) {
                    return null;
                }

                /** @var array $arProductIDList */
                $arProductIDList = Product::orderBy('popularity', 'desc')->lists('id');

                break;
            case self::SORT_NEW :
                $arProductIDList = Product::orderBy('id', 'desc')->lists('id');
                break;
            default:
                $arProductIDList = Product::lists('id');
                break;
        }

        if(empty($arProductIDList)) {
            return null;
        }

        //Set cache data
        CCache::forever($arCacheTags, $sCacheKey, $arProductIDList);

        return $arProductIDList;
    }

    /**
     * Get cached product ID list, filter by category ID
     * @param int $iCategoryID
     * @return array|null
     */
    public function getByCategory($iCategoryID)
    {
        if(empty($iCategoryID)) {
            return null;
        }
        
        //Get cache data
        $arCacheTags = [Plugin::CACHE_TAG, self::CACHE_TAG_LIST, CategoryItem::CACHE_TAG_ELEMENT];
        $sCacheKey = $iCategoryID;

        $arProductIDList = CCache::get($arCacheTags, $sCacheKey);
        if(!empty($arProductIDList)) {
            return $arProductIDList;
        }

        //Get product ID list
        /** @var array $arProductIDList */
        $arProductIDList = Product::getByCategory($iCategoryID)->lists('id');

        //Set cache data
        CCache::forever($arCacheTags, $sCacheKey, $arProductIDList);

        return $arProductIDList;
    }

    /**
     * Get active product ID list
     * @return array|null
     */
    public function getActiveList()
    {
        //Get cache data
        $arCacheTags = [Plugin::CACHE_TAG, self::CACHE_TAG_LIST];
        $sCacheKey = self::CACHE_TAG_LIST;

        $arProductIDList = CCache::get($arCacheTags, $sCacheKey);
        if(!empty($arProductIDList)) {
            return $arProductIDList;
        }

        //Get product ID list
        /** @var array $arProductIDList */
        $arProductIDList = Product::active()->lists('id');
        if(empty($arProductIDList)) {
            return null;
        }

        //Check active offers
        if(Settings::getValue('check_offer_active')) {

            //Get product list with active offers
            /** @var array $arProductIDListWithOffers */
            $arProductIDListWithOffers = Offer::active()->groupBy('product_id')->lists('product_id');
            if(empty($arProductIDListWithOffers)) {
                return null;
            }

            $arProductIDList = array_intersect($arProductIDList, $arProductIDListWithOffers);
            if(empty($arProductIDList)) {
                return null;
            }
        }

        //Set cache data
        CCache::forever($arCacheTags, $sCacheKey, $arProductIDList);

        return $arProductIDList;
    }
}