<?php

/**
 * Simpla CMS
 *
 * @copyright	2016 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */

require_once('Simpla.php');

class Coupons extends Simpla
{

    /**
     * Функция возвращает купон по его id или url
     * (в зависимости от типа аргумента, int - id, string - code)
     *
     * @param $id
     * @return bool|object|string
     */
    public function get_coupon($id)
    {
        if (gettype($id) == 'string') {
            $where = $this->db->placehold('WHERE c.code=? ', $id);
        } else {
            $where = $this->db->placehold('WHERE c.id=? ', $id);
        }

        $query = $this->db->placehold("SELECT c.id, 
                                              c.code, 
                                              c.value, 
                                              c.type, 
                                              c.expire, 
                                              c.min_order_price, 
                                              c.single,
                                              c.usages,
										      ((DATE(NOW()) <= DATE(c.expire) OR c.expire IS NULL) AND (c.usages=0 OR NOT c.single)) AS valid
										FROM __coupons c
										    $where
										LIMIT 1");
        if ($this->db->query($query)) {
            return $this->db->result();
        } else {
            return false;
        }
    }

    /**
     * Функция возвращает массив купонов, удовлетворяющих фильтру
     *
     * @param array $filter
     * @return array|bool
     */
    public function get_coupons($filter = array())
    {
        // По умолчанию
        $limit = 1000;
        $page = 1;
        $coupon_id_filter = '';
        $valid_filter = '';

        if (isset($filter['limit'])) {
            $limit = max(1, intval($filter['limit']));
        }

        if (isset($filter['page'])) {
            $page = max(1, intval($filter['page']));
        }

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if (!empty($filter['id'])) {
            $coupon_id_filter = $this->db->placehold('AND c.id IN( ?@ )', (array)$filter['id']);
        }

        if (isset($filter['valid'])) {
            if ($filter['valid']) {
                $valid_filter = $this->db->placehold('AND ((DATE(NOW()) <= DATE(c.expire) OR c.expire IS NULL) AND (c.usages=0 OR NOT c.single))');
            } else {
                $valid_filter = $this->db->placehold('AND NOT ((DATE(NOW()) <= DATE(c.expire) OR c.expire IS NULL) AND (c.usages=0 OR NOT c.single))');
            }
        }



        $query = $this->db->placehold("SELECT c.id, 
                                              c.code, 
                                              c.value, 
                                              c.type, 
                                              c.expire, 
                                              c.min_order_price, 
                                              c.single, 
                                              c.usages,
                                              ((DATE(NOW()) <= DATE(c.expire) OR c.expire IS NULL) AND (c.usages=0 OR NOT c.single)) AS valid
										FROM __coupons c
										WHERE 1
											$coupon_id_filter
											$valid_filter
										ORDER BY valid DESC, c.id DESC
										$sql_limit", $this->settings->date_format);

        $this->db->query($query);
        return $this->db->results();
    }


    /**
     * Функция вычисляет количество постов, удовлетворяющих фильтру
     *
     * @param array $filter
     * @return bool|object|string
     */
    public function count_coupons($filter = array())
    {
        $coupon_id_filter = '';
        $valid_filter = '';

        if (!empty($filter['id'])) {
            $coupon_id_filter = $this->db->placehold('AND c.id IN( ?@ )', (array)$filter['id']);
        }

        if (isset($filter['valid'])) {
            $valid_filter = $this->db->placehold('AND ((DATE(NOW()) <= DATE(c.expire) OR c.expire IS NULL) AND (c.usages=0 OR NOT c.single))');
        }

        $query = $this->db->placehold("SELECT COUNT(DISTINCT c.id) AS count
										FROM __coupons c
										WHERE 1
											$coupon_id_filter
											$valid_filter");

        if ($this->db->query($query)) {
            return $this->db->result('count');
        } else {
            return false;
        }
    }

    /**
     * Создание купона
     *
     * @param $coupon
     * @return int|bool
     */
    public function add_coupon($coupon)
    {
        if (empty($coupon->single)) {
            $coupon->single = 0;
        }

        $query = $this->db->placehold("INSERT INTO __coupons SET ?%", $coupon);
        if ($this->db->query($query)) {
            return $this->db->insert_id();
        }

        return false;
    }

    /**
     * Удалить купон
     *
     * @param $id
     * @param $coupon
     * @return bool|int
     */
    public function update_coupon($id, $coupon)
    {
        $query = $this->db->placehold("UPDATE __coupons SET ?% WHERE id IN( ?@ ) LIMIT ?", $coupon, (array)$id, count((array)$id));
        if (!$this->db->query($query)) {
            return false;
        }
        return $id;
    }


    /**
     * Удалить купон
     *
     * @param $id
     * @return bool
     */
    public function delete_coupon($id)
    {
        if (!empty($id)) {
            $query = $this->db->placehold("DELETE FROM __coupons WHERE id=? LIMIT 1", intval($id));
            if ($this->db->query($query)) {
                return true;
            }
        }
        return false;
    }
}
