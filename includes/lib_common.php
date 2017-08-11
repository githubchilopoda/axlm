<?php

/**
 * ECSHOP 公用函数库
 * ============================================================================
 * 版权所有 2005-2012 上海商派网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: lib_common.php 17217 2011-01-19 06:29:08Z liubo $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 创建像这样的查询: "IN('a','b')";
 *
 * @access   public
 * @param    mix      $item_list      列表数组或字符串
 * @param    string   $field_name     字段名称
 *
 * @return   void
 */
function db_create_in($item_list, $field_name = '')
{
    if (empty($item_list))
    {
        return $field_name . " IN ('') ";
    }
    else
    {
        if (!is_array($item_list))
        {
            $item_list = explode(',', $item_list);
        }
        $item_list = array_unique($item_list);
        $item_list_tmp = '';
        foreach ($item_list AS $item)
        {
            if ($item !== '')
            {
                $item_list_tmp .= $item_list_tmp ? ",'$item'" : "'$item'";
            }
        }
        if (empty($item_list_tmp))
        {
            return $field_name . " IN ('') ";
        }
        else
        {
            return $field_name . ' IN (' . $item_list_tmp . ') ';
        }
    }
}

/**
 * 验证输入的邮件地址是否合法
 *
 * @access  public
 * @param   string      $email      需要验证的邮件地址
 *
 * @return bool
 */
function is_email($user_email)
{
    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false)
    {
        if (preg_match($chars, $user_email))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    else
    {
        return false;
    }
}

/**
 * 验证输入的手机号码是否合法
 *
 * @access public
 * @param string $mobile_phone
 *        	需要验证的手机号码
 *        	
 * @return bool
 */
function is_mobile_phone ($mobile_phone)
{
	
	$chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$/";
	if(preg_match($chars, $mobile_phone))
	{
		return true;
	}
	
	return false;
}

/**
 * 检查是否为一个合法的时间格式
 *
 * @access  public
 * @param   string  $time
 * @return  void
 */
function is_time($time)
{
    $pattern = '/[\d]{4}-[\d]{1,2}-[\d]{1,2}\s[\d]{1,2}:[\d]{1,2}:[\d]{1,2}/';

    return preg_match($pattern, $time);
}

/**
 * 获得查询时间和次数，并赋值给smarty
 *
 * @access  public
 * @return  void
 */
function assign_query_info()
{
    if ($GLOBALS['db']->queryTime == '')
    {
        $query_time = 0;
    }
    else
    {
        if (PHP_VERSION >= '5.0.0')
        {
            $query_time = number_format(microtime(true) - $GLOBALS['db']->queryTime, 6);
        }
        else
        {
            list($now_usec, $now_sec)     = explode(' ', microtime());
            list($start_usec, $start_sec) = explode(' ', $GLOBALS['db']->queryTime);
            $query_time = number_format(($now_sec - $start_sec) + ($now_usec - $start_usec), 6);
        }
    }
    $GLOBALS['smarty']->assign('query_info', sprintf($GLOBALS['_LANG']['query_info'], $GLOBALS['db']->queryCount, $query_time));

    /* 内存占用情况 */
    if ($GLOBALS['_LANG']['memory_info'] && function_exists('memory_get_usage'))
    {
        $GLOBALS['smarty']->assign('memory_info', sprintf($GLOBALS['_LANG']['memory_info'], memory_get_usage() / 1048576));
    }

    /* 是否启用了 gzip */
    $gzip_enabled = gzip_enabled() ? $GLOBALS['_LANG']['gzip_enabled'] : $GLOBALS['_LANG']['gzip_disabled'];
    $GLOBALS['smarty']->assign('gzip_enabled', $gzip_enabled);
}

/**
 * 创建地区的返回信息
 *
 * @access  public
 * @param   array   $arr    地区数组 *
 * @return  void
 */
function region_result($parent, $sel_name, $type)
{
    global $cp;

    $arr = get_regions($type, $parent);
    foreach ($arr AS $v)
    {
        $region      =& $cp->add_node('region');
        $region_id   =& $region->add_node('id');
        $region_name =& $region->add_node('name');

        $region_id->set_data($v['region_id']);
        $region_name->set_data($v['region_name']);
    }
    $select_obj =& $cp->add_node('select');
    $select_obj->set_data($sel_name);
}

/**
 * 获得指定国家的所有省份
 *
 * @access      public
 * @param       int     country    国家的编号
 * @return      array
 */
function get_regions($type = 0, $parent = 0)
{
    $sql = 'SELECT region_id, region_name FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE region_type = '$type' AND parent_id = '$parent'";

    return $GLOBALS['db']->GetAll($sql);
}

/**
 * 获得配送区域中指定的配送方式的配送费用的计算参数
 *
 * @access  public
 * @param   int     $area_id        配送区域ID
 *
 * @return array;
 */
function get_shipping_config($area_id)
{
    /* 获得配置信息 */
    $sql = 'SELECT configure FROM ' . $GLOBALS['ecs']->table('shipping_area') . " WHERE shipping_area_id = '$area_id'";
    $cfg = $GLOBALS['db']->GetOne($sql);

    if ($cfg)
    {
        /* 拆分成配置信息的数组 */
        $arr = unserialize($cfg);
    }
    else
    {
        $arr = array();
    }

    return $arr;
}

/**
 * 初始化会员数据整合类
 *
 * @access  public
 * @return  object
 */
function &init_users()
{
    $set_modules = false;
    static $cls = null;
    if ($cls != null)
    {
        return $cls;
    }
    include_once(ROOT_PATH . 'includes/modules/integrates/' . $GLOBALS['_CFG']['integrate_code'] . '.php');
    $cfg = unserialize($GLOBALS['_CFG']['integrate_config']);
    $cls = new $GLOBALS['_CFG']['integrate_code']($cfg);

    return $cls;
}

/**
 * 获得指定分类下的子分类的数组
 *
 * @access  public
 * @param   int     $cat_id     分类的ID
 * @param   int     $selected   当前选中分类的ID
 * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param   int     $level      限定返回的级数。为0时返回所有级数
 * @param   int     $is_show_all 如果为true显示所有分类，如果为false隐藏不可见分类。
 * @return  mix
 */
function cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0, $is_show_all = true)
{
    static $res = NULL;

    if ($res === NULL)
    {
        $data = read_static_cache('cat_pid_releate');
        if ($data === false)
        {
            $sql = "SELECT c.cat_id, c.cat_name, c.measure_unit, c.parent_id, c.is_show, c.show_in_nav, c.grade, c.sort_order, COUNT(s.cat_id) AS has_children ".
                'FROM ' . $GLOBALS['ecs']->table('category') . " AS c ".
                "LEFT JOIN " . $GLOBALS['ecs']->table('category') . " AS s ON s.parent_id=c.cat_id ".
                "where c.is_virtual= '0' ".
                "GROUP BY c.cat_id ".
                'ORDER BY c.parent_id, c.sort_order ASC';
            $res = $GLOBALS['db']->getAll($sql);

            $sql = "SELECT cat_id, COUNT(*) AS goods_num " .
                    " FROM " . $GLOBALS['ecs']->table('goods') .
                    " WHERE is_delete = 0  " .
                    " GROUP BY cat_id";
            $res2 = $GLOBALS['db']->getAll($sql);

            $sql = "SELECT gc.cat_id, COUNT(*) AS goods_num " .
                    " FROM " . $GLOBALS['ecs']->table('goods_cat') . " AS gc , " . $GLOBALS['ecs']->table('goods') . " AS g " .
                    " WHERE g.goods_id = gc.goods_id AND g.is_delete = 0  " .
                    " GROUP BY gc.cat_id";
            $res3 = $GLOBALS['db']->getAll($sql);

            $newres = array();
            foreach($res2 as $k=>$v)
            {
                $newres[$v['cat_id']] = $v['goods_num'];
                foreach($res3 as $ks=>$vs)
                {
                    if($v['cat_id'] == $vs['cat_id'])
                    {
                    $newres[$v['cat_id']] = $v['goods_num'] + $vs['goods_num'];
                    }
                }
            }

            foreach($res as $k=>$v)
            {
                $res[$k]['goods_num'] = !empty($newres[$v['cat_id']]) ? $newres[$v['cat_id']] : 0;
            }
            //如果数组过大，不采用静态缓存方式
            if (count($res) <= 1000)
            {
                write_static_cache('cat_pid_releate', $res);
            }
        }
        else
        {
            $res = $data;
        }
    }

    if (empty($res) == true)
    {
        return $re_type ? '' : array();
    }

    $options = cat_options($cat_id, $res); // 获得指定分类下的子分类的数组

    $children_level = 99999; //大于这个分类的将被删除
    if ($is_show_all == false)
    {
        foreach ($options as $key => $val)
        {
            if ($val['level'] > $children_level)
            {
                unset($options[$key]);
            }
            else
            {
                if ($val['is_show'] == 0)
                {
                    unset($options[$key]);
                    if ($children_level > $val['level'])
                    {
                        $children_level = $val['level']; //标记一下，这样子分类也能删除
                    }
                }
                else
                {
                    $children_level = 99999; //恢复初始值
                }
            }
        }
    }

    /* 截取到指定的缩减级别 */
    if ($level > 0)
    {
        if ($cat_id == 0)
        {
            $end_level = $level;
        }
        else
        {
            $first_item = reset($options); // 获取第一个元素
            $end_level  = $first_item['level'] + $level;
        }

        /* 保留level小于end_level的部分 */
        foreach ($options AS $key => $val)
        {
            if ($val['level'] >= $end_level)
            {
                unset($options[$key]);
            }
        }
    }

    if ($re_type == true)
    {
        $select = '';
        foreach ($options AS $var)
        {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
            $select .= '>';
            if ($var['level'] > 0)
            {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars(addslashes($var['cat_name']), ENT_QUOTES) . '</option>';
        }

        return $select;
    }
    else
    {
        foreach ($options AS $key => $value)
        {
            $options[$key]['url'] = build_uri('category', array('cid' => $value['cat_id']), $value['cat_name']);
        }

        return $options;
    }
}

function cat_list1($cat_id = 0, $selected = 0, $re_type = true, $level = 0, $is_show_all = true)
{
    static $res = NULL;

    if ($res === NULL)
    {
        $data = read_static_cache('cat_pid_releate_virtual');
        if ($data === false)
        {
            $sql = "SELECT c.cat_id, c.cat_name, c.measure_unit, c.parent_id, c.is_show, c.show_in_nav, c.grade, c.sort_order, COUNT(s.cat_id) AS has_children,c.is_virtual ".
                'FROM ' . $GLOBALS['ecs']->table('category') . " AS c ".
                "LEFT JOIN " . $GLOBALS['ecs']->table('category') . " AS s ON s.parent_id=c.cat_id ".
                 "where c.is_virtual= '1' ".
                "GROUP BY c.cat_id ".
                'ORDER BY c.parent_id, c.sort_order ASC';
            $res = $GLOBALS['db']->getAll($sql);
            $sql = "SELECT cat_id, COUNT(*) AS goods_num " .
                    " FROM " . $GLOBALS['ecs']->table('goods') .
                    " WHERE is_delete = 0  " .
                    " GROUP BY cat_id";
            $res2 = $GLOBALS['db']->getAll($sql);

            $sql = "SELECT gc.cat_id, COUNT(*) AS goods_num " .
                    " FROM " . $GLOBALS['ecs']->table('goods_cat') . " AS gc , " . $GLOBALS['ecs']->table('goods') . " AS g " .
                    " WHERE g.goods_id = gc.goods_id AND g.is_delete = 0  " .
                    " GROUP BY gc.cat_id";
            $res3 = $GLOBALS['db']->getAll($sql);

            $newres = array();
            foreach($res2 as $k=>$v)
            {
                $newres[$v['cat_id']] = $v['goods_num'];
                foreach($res3 as $ks=>$vs)
                {
                    if($v['cat_id'] == $vs['cat_id'])
                    {
                    $newres[$v['cat_id']] = $v['goods_num'] + $vs['goods_num'];
                    }
                }
            }

            foreach($res as $k=>$v)
            {
                $res[$k]['goods_num'] = !empty($newres[$v['cat_id']]) ? $newres[$v['cat_id']] : 0;
            }
            //如果数组过大，不采用静态缓存方式
            if (count($res) <= 1000)
            {
                write_static_cache('cat_pid_releate_virtual', $res);
            }
        }
        else
        {
            $res = $data;
        }
    }

    if (empty($res) == true)
    {
        return $re_type ? '' : array();
    }

    $options = cat_options1($cat_id, $res); // 获得指定分类下的子分类的数组
    $children_level = 99999; //大于这个分类的将被删除
    if ($is_show_all == false)
    {
        foreach ($options as $key => $val)
        {
            if ($val['level'] > $children_level)
            {
                unset($options[$key]);
            }
            else
            {
                if ($val['is_show'] == 0)
                {
                    unset($options[$key]);
                    if ($children_level > $val['level'])
                    {
                        $children_level = $val['level']; //标记一下，这样子分类也能删除
                    }
                }
                else
                {
                    $children_level = 99999; //恢复初始值
                }
            }
        }
    }

    /* 截取到指定的缩减级别 */
    if ($level > 0)
    {
        if ($cat_id == 0)
        {
            $end_level = $level;
        }
        else
        {
            $first_item = reset($options); // 获取第一个元素
            $end_level  = $first_item['level'] + $level;
        }

        /* 保留level小于end_level的部分 */
        foreach ($options AS $key => $val)
        {
            if ($val['level'] >= $end_level)
            {
                unset($options[$key]);
            }
        }
    }

    if ($re_type == true)
    {
        $select = '';
        foreach ($options AS $var)
        {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
            $select .= '>';
            if ($var['level'] > 0)
            {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars(addslashes($var['cat_name']), ENT_QUOTES) . '</option>';
        }

        return $select;
    }
    else
    {
        foreach ($options AS $key => $value)
        {
            $options[$key]['url'] = build_uri('category', array('cid' => $value['cat_id']), $value['cat_name']);
        }

        return $options;
    }
}


/**
 * 过滤和排序所有分类，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param   int     $cat_id     上级分类ID
 * @param   array   $arr        含有所有分类的数组
 * @param   int     $level      级别
 * @return  void
 */
function cat_options1($spec_cat_id, $arr)
{
    static $cat_options = array();

    if (isset($cat_options[$spec_cat_id]))
    {
        return $cat_options[$spec_cat_id];
    }

    if (!isset($cat_options[0]))
    {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = array();
        $data = read_static_cache('cat_option_static_virtual');
        if ($data === false)
        {
            while (!empty($arr))
            {
                foreach ($arr AS $key => $value)
                {
                    $cat_id = $value['cat_id'];
                    if ($level == 0 && $last_cat_id == 0)
                    {
                        if ($value['parent_id'] > 0)
                        {
                            break;
                        }

                        $options[$cat_id]          = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id']    = $cat_id;
                        $options[$cat_id]['name']  = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0)
                        {
                            continue;
                        }
                        $last_cat_id  = $cat_id;
                        $cat_id_array = array($cat_id);
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_cat_id)
                    {
                        $options[$cat_id]          = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id']    = $cat_id;
                        $options[$cat_id]['name']  = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0)
                        {
                            if (end($cat_id_array) != $last_cat_id)
                            {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id    = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    }
                    elseif ($value['parent_id'] > $last_cat_id)
                    {
                        break;
                    }
                }

                $count = count($cat_id_array);
                if ($count > 1)
                {
                    $last_cat_id = array_pop($cat_id_array);
                }
                elseif ($count == 1)
                {
                    if ($last_cat_id != end($cat_id_array))
                    {
                        $last_cat_id = end($cat_id_array);
                    }
                    else
                    {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = array();
                        continue;
                    }
                }

                if ($last_cat_id && isset($level_array[$last_cat_id]))
                {
                    $level = $level_array[$last_cat_id];
                }
                else
                {
                    $level = 0;
                }
            }
            //如果数组过大，不采用静态缓存方式
            if (count($options) <= 2000)
            {
                write_static_cache('cat_option_static_virtual', $options);
            }
        }
        else
        {
            $options = $data;
        }
        $cat_options[0] = $options;
    }
    else
    {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id)
    {
        return $options;
    }
    else
    {
        if (empty($options[$spec_cat_id]))
        {
            return array();
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options AS $key => $value)
        {
            if ($key != $spec_cat_id)
            {
                unset($options[$key]);
            }
            else
            {
                break;
            }
        }

        $spec_cat_id_array = array();
        foreach ($options AS $key => $value)
        {
            if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                ($spec_cat_id_level > $value['level']))
            {
                break;
            }
            else
            {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;

        return $spec_cat_id_array;
    }
}


/**
 * 过滤和排序所有分类，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param   int     $cat_id     上级分类ID
 * @param   array   $arr        含有所有分类的数组
 * @param   int     $level      级别
 * @return  void
 */
function cat_options($spec_cat_id, $arr)
{
    static $cat_options = array();

    if (isset($cat_options[$spec_cat_id]))
    {
        return $cat_options[$spec_cat_id];
    }

    if (!isset($cat_options[0]))
    {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = array();
        $data = read_static_cache('cat_option_static');
        if ($data === false)
        {
            while (!empty($arr))
            {
                foreach ($arr AS $key => $value)
                {
                    $cat_id = $value['cat_id'];
                    if ($level == 0 && $last_cat_id == 0)
                    {
                        if ($value['parent_id'] > 0)
                        {
                            break;
                        }

                        $options[$cat_id]          = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id']    = $cat_id;
                        $options[$cat_id]['name']  = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0)
                        {
                            continue;
                        }
                        $last_cat_id  = $cat_id;
                        $cat_id_array = array($cat_id);
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_cat_id)
                    {
                        $options[$cat_id]          = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id']    = $cat_id;
                        $options[$cat_id]['name']  = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0)
                        {
                            if (end($cat_id_array) != $last_cat_id)
                            {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id    = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    }
                    elseif ($value['parent_id'] > $last_cat_id)
                    {
                        break;
                    }
                }

                $count = count($cat_id_array);
                if ($count > 1)
                {
                    $last_cat_id = array_pop($cat_id_array);
                }
                elseif ($count == 1)
                {
                    if ($last_cat_id != end($cat_id_array))
                    {
                        $last_cat_id = end($cat_id_array);
                    }
                    else
                    {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = array();
                        continue;
                    }
                }

                if ($last_cat_id && isset($level_array[$last_cat_id]))
                {
                    $level = $level_array[$last_cat_id];
                }
                else
                {
                    $level = 0;
                }
            }
            //如果数组过大，不采用静态缓存方式
            if (count($options) <= 2000)
            {
                write_static_cache('cat_option_static', $options);
            }
        }
        else
        {
            $options = $data;
        }
        $cat_options[0] = $options;
    }
    else
    {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id)
    {
        return $options;
    }
    else
    {
        if (empty($options[$spec_cat_id]))
        {
            return array();
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options AS $key => $value)
        {
            if ($key != $spec_cat_id)
            {
                unset($options[$key]);
            }
            else
            {
                break;
            }
        }

        $spec_cat_id_array = array();
        foreach ($options AS $key => $value)
        {
            if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                ($spec_cat_id_level > $value['level']))
            {
                break;
            }
            else
            {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;

        return $spec_cat_id_array;
    }
}

/* 代码增加 By  www.cfweb2015.com Start */
/**
 * 过滤和排序所有分类，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param   int     $cat_id     上级分类ID
 * @param   array   $arr        含有所有分类的数组
 * @param   int     $level      级别
 * @return  void
 */
function cat_options2($spec_cat_id, $arr)
{
    static $cat_options = array();

    if (isset($cat_options[$spec_cat_id]))
    {
        return $cat_options[$spec_cat_id];
    }

    if (!isset($cat_options[0]))
    {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = array();
        $data = read_static_cache('cat_option_static2');
        if ($data === false)
        {
            while (!empty($arr))
            {
                foreach ($arr AS $key => $value)
                {
                    $cat_id = $value['cat_id'];
                    if ($level == 0 && $last_cat_id == 0)
                    {
                        if ($value['parent_id'] > 0)
                        {
                            break;
                        }

                        $options[$cat_id]          = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id']    = $cat_id;
                        $options[$cat_id]['name']  = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] == 0)
                        {
                            continue;
                        }
                        $last_cat_id  = $cat_id;
                        $cat_id_array = array($cat_id);
                        $level_array[$last_cat_id] = ++$level;
                        continue;
                    }

                    if ($value['parent_id'] == $last_cat_id)
                    {
                        $options[$cat_id]          = $value;
                        $options[$cat_id]['level'] = $level;
                        $options[$cat_id]['id']    = $cat_id;
                        $options[$cat_id]['name']  = $value['cat_name'];
                        unset($arr[$key]);

                        if ($value['has_children'] > 0)
                        {
                            if (end($cat_id_array) != $last_cat_id)
                            {
                                $cat_id_array[] = $last_cat_id;
                            }
                            $last_cat_id    = $cat_id;
                            $cat_id_array[] = $cat_id;
                            $level_array[$last_cat_id] = ++$level;
                        }
                    }
                    elseif ($value['parent_id'] > $last_cat_id)
                    {
                        break;
                    }
                }

                $count = count($cat_id_array);
                if ($count > 1)
                {
                    $last_cat_id = array_pop($cat_id_array);
                }
                elseif ($count == 1)
                {
                    if ($last_cat_id != end($cat_id_array))
                    {
                        $last_cat_id = end($cat_id_array);
                    }
                    else
                    {
                        $level = 0;
                        $last_cat_id = 0;
                        $cat_id_array = array();
                        continue;
                    }
                }

                if ($last_cat_id && isset($level_array[$last_cat_id]))
                {
                    $level = $level_array[$last_cat_id];
                }
                else
                {
                    $level = 0;
                }
            }
            //如果数组过大，不采用静态缓存方式
            if (count($options) <= 2000)
            {
                write_static_cache('cat_option_static2', $options);
            }
        }
        else
        {
            $options = $data;
        }
        $cat_options[0] = $options;
    }
    else
    {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id)
    {
        return $options;
    }
    else
    {
        if (empty($options[$spec_cat_id]))
        {
            return array();
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options AS $key => $value)
        {
            if ($key != $spec_cat_id)
            {
                unset($options[$key]);
            }
            else
            {
                break;
            }
        }

        $spec_cat_id_array = array();
        foreach ($options AS $key => $value)
        {
            if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                ($spec_cat_id_level > $value['level']))
            {
                break;
            }
            else
            {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;

        return $spec_cat_id_array;
    }
}
/* 代码增加 By  www.cfweb2015.com End */

/**
 * 载入配置信息
 *
 * @access  public
 * @return  array
 */
function load_config()
{
    $arr = array();

    $data = read_static_cache('shop_config');
    if ($data === false)
    {
        $sql = 'SELECT code, value FROM ' . $GLOBALS['ecs']->table('shop_config') . ' WHERE parent_id > 0';
        $res = $GLOBALS['db']->getAll($sql);

        foreach ($res AS $row)
        {
            $arr[$row['code']] = $row['value'];
        }

        /* 对数值型设置处理 */
        $arr['watermark_alpha']      = intval($arr['watermark_alpha']);
        $arr['market_price_rate']    = floatval($arr['market_price_rate']);
        $arr['integral_scale']       = floatval($arr['integral_scale']);
        //$arr['integral_percent']     = floatval($arr['integral_percent']);
        $arr['cache_time']           = intval($arr['cache_time']);
        $arr['thumb_width']          = intval($arr['thumb_width']);
        $arr['thumb_height']         = intval($arr['thumb_height']);
        $arr['image_width']          = intval($arr['image_width']);
        $arr['image_height']         = intval($arr['image_height']);
        $arr['best_number']          = !empty($arr['best_number']) && intval($arr['best_number']) > 0 ? intval($arr['best_number'])     : 3;
        $arr['new_number']           = !empty($arr['new_number']) && intval($arr['new_number']) > 0 ? intval($arr['new_number'])      : 3;
        $arr['hot_number']           = !empty($arr['hot_number']) && intval($arr['hot_number']) > 0 ? intval($arr['hot_number'])      : 3;
        $arr['promote_number']       = !empty($arr['promote_number']) && intval($arr['promote_number']) > 0 ? intval($arr['promote_number'])  : 3;
        $arr['top_number']           = intval($arr['top_number'])      > 0 ? intval($arr['top_number'])      : 10;
        $arr['history_number']       = intval($arr['history_number'])  > 0 ? intval($arr['history_number'])  : 5;
        $arr['comments_number']      = intval($arr['comments_number']) > 0 ? intval($arr['comments_number']) : 5;
        $arr['article_number']       = intval($arr['article_number'])  > 0 ? intval($arr['article_number'])  : 5;
        $arr['page_size']            = intval($arr['page_size'])       > 0 ? intval($arr['page_size'])       : 10;
        $arr['bought_goods']         = intval($arr['bought_goods']);
        $arr['goods_name_length']    = intval($arr['goods_name_length']);
        $arr['top10_time']           = intval($arr['top10_time']);
        $arr['goods_gallery_number'] = intval($arr['goods_gallery_number']) ? intval($arr['goods_gallery_number']) : 5;
        $arr['no_picture']           = !empty($arr['no_picture']) ? str_replace('../', './', $arr['no_picture']) : 'images/no_picture.gif'; // 修改默认商品图片的路径
        // 代码修改_start www.cfweb2015.com
//         $arr['qq']                   = !empty($arr['qq']) ? $arr['qq'] : '';
//         $arr['ww']                   = !empty($arr['ww']) ? $arr['ww'] : '';
        $arr['qq'] = '';
        $arr['ww'] = '';
        $qq = $GLOBALS['db']->getAll("SELECT cus_no FROM " . $GLOBALS['ecs']->table('chat_third_customer') . " WHERE is_master = 1 AND cus_type = 0 AND supplier_id = 0");
        $ww = $GLOBALS['db']->getAll("SELECT cus_no FROM " . $GLOBALS['ecs']->table('chat_third_customer') . " WHERE is_master = 1 AND cus_type = 1 AND supplier_id = 0");
        foreach ($qq as $k => $v)
        {
            $arr['qq'] = $arr['qq'] . ',' . $v['cus_no'];
        }
        foreach ($ww as $k => $v)
        {
            $arr['ww'] = $arr['ww'] . ',' . $v['cus_no'];
        }
        // 代码修改_end www.cfweb2015.com
        $arr['default_storage']      = isset($arr['default_storage']) ? intval($arr['default_storage']) : 1;
        $arr['min_goods_amount']     = isset($arr['min_goods_amount']) ? floatval($arr['min_goods_amount']) : 0;
        $arr['one_step_buy']         = empty($arr['one_step_buy']) ? 0 : 1;
        $arr['invoice_type']         = empty($arr['invoice_type']) ? array('type' => array(), 'rate' => array()) : unserialize($arr['invoice_type']);
        $arr['show_order_type']      = isset($arr['show_order_type']) ? $arr['show_order_type'] : 0;    // 显示方式默认为列表方式
        $arr['help_open']            = isset($arr['help_open']) ? $arr['help_open'] : 1;    // 显示方式默认为列表方式
        /* fulltext_search_add_START_www.cfweb2015.com */
	$arr['fulltext_search']            = isset($arr['fulltext_search']) ? $arr['fulltext_search'] : 0;  
        /* fulltext_search_add_END_www.cfweb2015.com */
        $arr['shop_opint']            = isset($arr['shop_opint']) ? $arr['shop_opint'] : 0;  

        if (!isset($GLOBALS['_CFG']['ecs_version']))
        {
            /* 如果没有版本号则默认为2.0.5 */
            $GLOBALS['_CFG']['ecs_version'] = 'v2.0.5';
        }

        //限定语言项
        $lang_array = array('zh_cn', 'zh_tw', 'en_us');
        if (empty($arr['lang']) || !in_array($arr['lang'], $lang_array))
        {
            $arr['lang'] = 'zh_cn'; // 默认语言为简体中文
        }

        if (empty($arr['integrate_code']))
        {
            $arr['integrate_code'] = 'ecshop'; // 默认的会员整合插件为 ecshop
        }
        write_static_cache('shop_config', $arr);
    }
    else
    {
        $arr = $data;
    }

    return $arr;
}

/**
 * 取得品牌列表
 * @return array 品牌列表 id => name
 */
// 代码修改_start_derek20150129admin_goods  www.cfweb2015.com
function get_brand_list($t = false)
// 代码修改_end_derek20150129admin_goods  www.cfweb2015.com
{
    $sql = 'SELECT brand_id, brand_name FROM ' . $GLOBALS['ecs']->table('brand') . ' ORDER BY sort_order';
    $res = $GLOBALS['db']->getAll($sql);

    $brand_list = array();
    foreach ($res AS $row)
    {
        // 代码修改_start_derek20150129admin_goods  www.cfweb2015.com
		
		if ($t == true)
		{
			$brand_list[$row['brand_id']]['name'] = addslashes($row['brand_name']);
			$brand_list[$row['brand_id']]['name_pinyin'] = Pinyin($brand_list[$row['brand_id']]['name'],1,1);
			$brand_list[$row['brand_id']]['name_p'] = substr($brand_list[$row['brand_id']]['name_pinyin'],0,1);
		}
		else
		{
			$brand_list[$row['brand_id']] = addslashes($row['brand_name']);
		}
		// 代码修改_end_derek20150129admin_goods  www.cfweb2015.com
    }

    return $brand_list;
}

/**
 * 获得某个分类下
 *
 * @access  public
 * @param   int     $cat
 * @return  array
 */
function get_brands($cat = 0, $app = 'brand')
{
    global $page_libs;
    $template = basename(PHP_SELF);
    $template = substr($template, 0, strrpos($template, '.'));
    include_once(ROOT_PATH . ADMIN_PATH . '/includes/lib_template.php');
    static $static_page_libs = null;
    if ($static_page_libs == null)
    {
            $static_page_libs = $page_libs;
    }

    $children = ($cat > 0) ? ' AND ' . get_children($cat) : '';

    $sql = "SELECT b.brand_id, b.brand_name, b.brand_logo, b.brand_desc, COUNT(*) AS goods_num, IF(b.brand_logo > '', '1', '0') AS tag ".
            "FROM " . $GLOBALS['ecs']->table('brand') . "AS b, ".
                $GLOBALS['ecs']->table('goods') . " AS g ".
            "WHERE g.brand_id = b.brand_id $children AND is_show = 1 " .
            " AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".
            "GROUP BY b.brand_id HAVING goods_num > 0 ORDER BY tag DESC, b.sort_order ASC";
    if (isset($static_page_libs[$template]['/library/brands.lbi']))
    {
        $num = get_library_number("brands");
        $sql .= " LIMIT $num ";
    }
    $row = $GLOBALS['db']->getAll($sql);

    foreach ($row AS $key => $val)
    {
        $row[$key]['url'] = build_uri($app, array('cid' => $cat, 'bid' => $val['brand_id']), $val['brand_name']);
        $row[$key]['brand_desc'] = htmlspecialchars($val['brand_desc'],ENT_QUOTES);
    }

    return $row;
}

/**
 *  所有的促销活动信息
 *
 * @access  public
 * @return  array
 */
function get_promotion_info($goods_id = '',$suppid=-1)
{
    $snatch = array();
    $group = array();
    $auction = array();
    $package = array();
    $favourable = array();

    $gmtime = gmtime();
    $where_suppid = '';
    $suppid = intval($suppid);
    if($suppid>-1){
    	$where_suppid = ' AND supplier_id='.$suppid;
    }
    $sql = 'SELECT act_id, act_name, act_type, start_time, end_time FROM ' . $GLOBALS['ecs']->table('goods_activity') . " WHERE is_finished=0 AND start_time <= '$gmtime' AND end_time >= '$gmtime'";
    if(!empty($goods_id))
    {
        $sql .= " AND goods_id = '$goods_id'";
    }
    $res = $GLOBALS['db']->getAll($sql);
    foreach ($res as $data)
    {
        switch ($data['act_type'])
        {
            case GAT_SNATCH: //夺宝奇兵
                $snatch[$data['act_id']]['act_name'] = $data['act_name'];
                $snatch[$data['act_id']]['url'] = build_uri('snatch', array('sid' => $data['act_id']));
                $snatch[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                $snatch[$data['act_id']]['sort'] = $data['start_time'];
                $snatch[$data['act_id']]['type'] = 'snatch';
                break;

            case GAT_GROUP_BUY: //团购
                $group[$data['act_id']]['act_name'] = $data['act_name'];
                $group[$data['act_id']]['url'] = build_uri('group_buy', array('gbid' => $data['act_id']));
                $group[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                $group[$data['act_id']]['sort'] = $data['start_time'];
                $group[$data['act_id']]['type'] = 'group_buy';
                break;

            case GAT_AUCTION: //拍卖
                $auction[$data['act_id']]['act_name'] = $data['act_name'];
                $auction[$data['act_id']]['url'] = build_uri('auction', array('auid' => $data['act_id']));
                $auction[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                $auction[$data['act_id']]['sort'] = $data['start_time'];
                $auction[$data['act_id']]['type'] = 'auction';
                break;

            case GAT_PACKAGE: //礼包
                $package[$data['act_id']]['act_name'] = $data['act_name'];
                $package[$data['act_id']]['url'] = 'package.php#' . $data['act_id'];
                $package[$data['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $data['start_time']), local_date('Y-m-d', $data['end_time']));
                $package[$data['act_id']]['sort'] = $data['start_time'];
                $package[$data['act_id']]['type'] = 'package';
                break;
        }
    }

    $user_rank = ',' . $_SESSION['user_rank'] . ',';
    $favourable = array();
    $sql = 'SELECT act_id, act_range,act_type, act_range_ext, act_name,gift, start_time, end_time FROM ' . $GLOBALS['ecs']->table('favourable_activity') . " WHERE start_time <= '$gmtime' AND end_time >= '$gmtime' $where_suppid";
    if(!empty($goods_id))
    {
        $sql .= " AND CONCAT(',', user_rank, ',') LIKE '%" . $user_rank . "%'";
    }
    $res = $GLOBALS['db']->getAll($sql);

    if(empty($goods_id))
    {
        foreach ($res as $rows)
        {
            $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
            $favourable[$rows['act_id']]['url'] = 'activity.php';
            $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
            $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
			$favourable[$rows['act_id']]['act_range'] = $rows['act_range'];
			$bb = unserialize($rows['gift']);
				if(is_array($bb))
            	{
					foreach($bb as $k=>$v)
					{
						$bb[$k]['thumb'] = get_image_path($v['id'], $GLOBALS['db']->getOne("SELECT goods_thumb FROM " . $GLOBALS['ecs']->table('goods') . "WHERE goods_id = '" . $v['id'] . "'"), true);
					}
				}
				switch($rows['act_type'])
				{
				case 0:
					$favourable[$rows['act_id']]['act_type'] = "满赠";
					break;
				case 1:
					$favourable[$rows['act_id']]['act_type'] = "减免";
					break;
				case 2:
					$favourable[$rows['act_id']]['act_type'] = "折扣";
					break;
				}
			$favourable[$rows['act_id']]['gift'] = $bb;
            $favourable[$rows['act_id']]['type'] = 'favourable';
        }
    }
    else
    {
        $sql = "SELECT cat_id, brand_id FROM " . $GLOBALS['ecs']->table('goods') .
           "WHERE goods_id = '$goods_id'";
        $row = $GLOBALS['db']->getRow($sql);
        $category_id = $row['cat_id'];
        $brand_id = $row['brand_id'];

        foreach ($res as $rows)
        {
            if ($rows['act_range'] == FAR_ALL)
            {
                $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                $favourable[$rows['act_id']]['url'] = 'activity.php';
                $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
				$bb = unserialize($rows['gift']);
				if(is_array($bb))
            	{
					foreach($bb as $k=>$v)
					{
						$bb[$k]['thumb'] = get_image_path($v['id'], $GLOBALS['db']->getOne("SELECT goods_thumb FROM " . $GLOBALS['ecs']->table('goods') . "WHERE goods_id = '" . $v['id'] . "'"), true);
					}
				}
				switch($rows['act_type'])
				{
				case 0:
					$favourable[$rows['act_id']]['act_type'] = "满赠";
					break;
				case 1:
					$favourable[$rows['act_id']]['act_type'] = "减免";
					break;
				case 2:
					$favourable[$rows['act_id']]['act_type'] = "折扣";
					break;
				}
				$favourable[$rows['act_id']]['gift'] = $bb;
				$favourable[$rows['act_id']]['act_range'] = $rows['act_range'];
                $favourable[$rows['act_id']]['type'] = 'favourable';
            }
            elseif ($rows['act_range'] == FAR_CATEGORY)
            {
                /* 找出分类id的子分类id */
                $id_list = array();
                $raw_id_list = explode(',', $rows['act_range_ext']);
                foreach ($raw_id_list as $id)
                {
                    $id_list = array_merge($id_list, array_keys(cat_list($id, 0, false)));
                }
                $ids = join(',', array_unique($id_list));

                if (strpos(',' . $ids . ',', ',' . $category_id . ',') !== false)
                {
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['url'] = 'activity.php';
                    $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
					$bb = unserialize($rows['gift']);
					if(is_array($bb))
					{
						foreach($bb as $k=>$v)
						{
							$bb[$k]['thumb'] = get_image_path($v['id'], $GLOBALS['db']->getOne("SELECT goods_thumb FROM " . $GLOBALS['ecs']->table('goods') . "WHERE goods_id = '" . $v['id'] . "'"), true);
						}
					}
					switch($rows['act_type'])
				{
				case 0:
					$favourable[$rows['act_id']]['act_type'] = "满赠";
					break;
				case 1:
					$favourable[$rows['act_id']]['act_type'] = "减免";
					break;
				case 2:
					$favourable[$rows['act_id']]['act_type'] = "折扣";
					break;
				}
					$favourable[$rows['act_id']]['gift'] = $bb;
					$favourable[$rows['act_id']]['act_range'] = $rows['act_range'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                }
            }
            elseif ($rows['act_range'] == FAR_BRAND)
            {
                if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $brand_id . ',') !== false)
                {
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['url'] = 'activity.php';
                    $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
					$bb = unserialize($rows['gift']);
					if(is_array($bb))
					{
						foreach($bb as $k=>$v)
						{
							$bb[$k]['thumb'] = get_image_path($v['id'], $GLOBALS['db']->getOne("SELECT goods_thumb FROM " . $GLOBALS['ecs']->table('goods') . "WHERE goods_id = '" . $v['id'] . "'"), true);
						}
					}
					switch($rows['act_type'])
				{
				case 0:
					$favourable[$rows['act_id']]['act_type'] = "满赠";
					break;
				case 1:
					$favourable[$rows['act_id']]['act_type'] = "减免";
					break;
				case 2:
					$favourable[$rows['act_id']]['act_type'] = "折扣";
					break;
				}
					$favourable[$rows['act_id']]['gift'] = $bb;
					$favourable[$rows['act_id']]['act_range'] = $rows['act_range'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                }
            }
            elseif ($rows['act_range'] == FAR_GOODS)
            {
                if (strpos(',' . $rows['act_range_ext'] . ',', ',' . $goods_id . ',') !== false)
                {
                    $favourable[$rows['act_id']]['act_name'] = $rows['act_name'];
                    $favourable[$rows['act_id']]['url'] = 'activity.php';
                    $favourable[$rows['act_id']]['time'] = sprintf($GLOBALS['_LANG']['promotion_time'], local_date('Y-m-d', $rows['start_time']), local_date('Y-m-d', $rows['end_time']));
                    $favourable[$rows['act_id']]['sort'] = $rows['start_time'];
					$bb = unserialize($rows['gift']);
					if(is_array($bb))
					{
						foreach($bb as $k=>$v)
						{
							$bb[$k]['thumb'] = get_image_path($v['id'], $GLOBALS['db']->getOne("SELECT goods_thumb FROM " . $GLOBALS['ecs']->table('goods') . "WHERE goods_id = '" . $v['id'] . "'"), true);
						}
					}
					switch($rows['act_type'])
				{
				case 0:
					$favourable[$rows['act_id']]['act_type'] = "满赠";
					break;
				case 1:
					$favourable[$rows['act_id']]['act_type'] = "减免";
					break;
				case 2:
					$favourable[$rows['act_id']]['act_type'] = "折扣";
					break;
				}
					$favourable[$rows['act_id']]['gift'] = $bb;
					$favourable[$rows['act_id']]['act_range'] = $rows['act_range'];
                    $favourable[$rows['act_id']]['type'] = 'favourable';
                }
            }
        }
    }

//    if(!empty($goods_id))
//    {
//        return array('snatch'=>$snatch, 'group_buy'=>$group, 'auction'=>$auction, 'favourable'=>$favourable);
//    }

    $sort_time = array();
    $arr = array_merge($snatch, $group, $auction, $package, $favourable);
    foreach($arr as $key => $value)
    {
        $sort_time[] = $value['sort'];
    }
    array_multisort($sort_time, SORT_NUMERIC, SORT_DESC, $arr);

    return $arr;
}

/**
 * 获得指定分类下所有底层分类的ID
 *
 * @access  public
 * @param   integer     $cat        指定的分类ID
 * @param   string      $ext        表的前缀名称
 * @return  string
 */
function get_children($cat = 0,$ext='g')
{
    return $ext.'.cat_id ' . db_create_in(array_unique(array_merge(array($cat), array_keys(cat_list($cat, 0, false)))));
}

/**
 * 获得指定文章分类下所有底层分类的ID
 *
 * @access  public
 * @param   integer     $cat        指定的分类ID
 *
 * @return void
 */
function get_article_children ($cat = 0)
{
    return db_create_in(array_unique(array_merge(array($cat), array_keys(article_cat_list($cat, 0, false)))), 'cat_id');
}

/**
 * 获取邮件模板
 *
 * @access  public
 * @param:  $tpl_name[string]       模板代码
 *
 * @return array
 */
function get_mail_template($tpl_name)
{
    $sql = 'SELECT template_subject, is_html, template_content FROM ' . $GLOBALS['ecs']->table('mail_templates') . " WHERE template_code = '$tpl_name'";

    return $GLOBALS['db']->GetRow($sql);

}

/**
 * 记录订单操作记录
 *
 * @access  public
 * @param   string  $order_sn           订单编号
 * @param   integer $order_status       订单状态
 * @param   integer $shipping_status    配送状态
 * @param   integer $pay_status         付款状态
 * @param   string  $note               备注
 * @param   string  $username           用户名，用户自己的操作则为 buyer
 * @return  void
 */
function order_action($order_sn, $order_status, $shipping_status, $pay_status, $note = '', $username = null, $place = 0)
{
    if (is_null($username))
    {
        $username = $_SESSION['admin_name'];
    }

    $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('order_action') .
                ' (order_id, action_user, order_status, shipping_status, pay_status, action_place, action_note, log_time) ' .
            'SELECT ' .
                "order_id, '$username', '$order_status', '$shipping_status', '$pay_status', '$place', '$note', '" .gmtime() . "' " .
            'FROM ' . $GLOBALS['ecs']->table('order_info') . " WHERE order_sn = '$order_sn'";
    $GLOBALS['db']->query($sql);
}

/**
 * 格式化商品价格
 *
 * @access  public
 * @param   float   $price  商品价格
 * @return  string
 */
function price_format($price, $change_price = true)
{
    if($price==='')
    {
     $price=0;
    }
    if ($change_price && defined('ECS_ADMIN') === false)
    {
        switch ($GLOBALS['_CFG']['price_format'])
        {
            case 0:
                $price = number_format($price, 2, '.', '');
                break;
            case 1: // 保留不为 0 的尾数
                $price = preg_replace('/(.*)(\\.)([0-9]*?)0+$/', '\1\2\3', number_format($price, 2, '.', ''));

                if (substr($price, -1) == '.')
                {
                    $price = substr($price, 0, -1);
                }
                break;
            case 2: // 不四舍五入，保留1位
                $price = substr(number_format($price, 2, '.', ''), 0, -1);
                break;
            case 3: // 直接取整
                $price = intval($price);
                break;
            case 4: // 四舍五入，保留 1 位
                $price = number_format($price, 1, '.', '');
                break;
            case 5: // 先四舍五入，不保留小数
                $price = round($price);
                break;
        }
    }
    else
    {
        $price = number_format(floatval($price), 2, '.', '');
    }

    return sprintf($GLOBALS['_CFG']['currency_format'], $price);
}

/**
 * 返回订单中的虚拟商品
 *
 * @access  public
 * @param   int   $order_id   订单id值
 * @param   bool  $shipping   是否已经发货
 *
 * @return array()
 */
function get_virtual_goods($order_id, $shipping = false)
{
    if ($shipping)
    {
        $sql = 'SELECT goods_id, goods_name, goods_attr_id, send_number AS num, extension_code FROM '.
           $GLOBALS['ecs']->table('order_goods') .
           " WHERE order_id = '$order_id' AND extension_code <> '' AND is_real = 0";
    }
    else
    {
        $sql = 'SELECT goods_id, goods_name,goods_attr_id, (goods_number - send_number) AS num, extension_code FROM '.
           $GLOBALS['ecs']->table('order_goods') .
           " WHERE order_id = '$order_id' AND is_real = 0 AND (goods_number - send_number) >= 0 AND extension_code <> '' ";
    }
    $res = $GLOBALS['db']->getAll($sql);
    $virtual_goods = array();
    foreach ($res AS $row)
    {
        $goods_info = $GLOBALS['db']->getRow("select valid_date,supplier_id from ".$GLOBALS['ecs']->table('goods')." where goods_id = ".$row['goods_id']);
        $virtual_goods[$row['extension_code']][] = array('goods_id' => $row['goods_id'], 'goods_attr_id'=>$row['goods_attr_id'], 'goods_name' => $row['goods_name'], 'num' => $row['num'],'valid_date'=>$goods_info['valid_date'],'supplier_id'=>$goods_info['supplier_id']);
    }
    return $virtual_goods;
}

/**
 *  虚拟商品发货
 *
 * @access  public
 * @param   array  $virtual_goods   虚拟商品数组
 * @param   string $msg             错误信息
 * @param   string $order_sn        订单号。
 * @param   string $process         设定当前流程：split，发货分单流程；other，其他，默认。
 *
 * @return bool
 */
function virtual_goods_ship(&$virtual_goods, &$msg, $order_sn, $return_result = false, $process = 'other')
{
 
    $virtual_card = array();
    foreach ($virtual_goods AS $code => $goods_list)
    {
    
        /* 只处理虚拟卡 */
        if ($code == 'virtual_card')
        {
            foreach ($goods_list as $goods)
            {
                if (virtual_card_shipping($goods, $order_sn, $msg, $process))
                {
                    if ($return_result)
                    {
                        $virtual_card[] = array('goods_id'=>$goods['goods_id'], 'goods_name'=>$goods['goods_name'], 'info'=>virtual_card_result($order_sn, $goods));
                    }
                }
                else
                {
                    return false;
                }
            }
            $GLOBALS['smarty']->assign('virtual_card',      $virtual_card);
        }
        // 处理虚拟商品
        if ($code == 'virtual_good')
        {
   
            foreach ($goods_list as $goods)
            {
                if (virtual_goods_shipping($goods, $order_sn, $msg, $process))
                {
                    if ($return_result)
                    {
                        $virtual_card[] = array('goods_id'=>$goods['goods_id'], 'goods_name'=>$goods['goods_name'], 'info'=>virtual_goods_result($order_sn, $goods));
                    }
                }
                else
                {
                    return false;
                }
            }
            $GLOBALS['smarty']->assign('virtual_card',$virtual_card);
        }
    }

    return true;
}

/**
 *  虚拟卡发货
 *
 * @access  public
 * @param   string      $goods      商品详情数组
 * @param   string      $order_sn   本次操作的订单
 * @param   string      $msg        返回信息
 * @param   string      $process    设定当前流程：split，发货分单流程；other，其他，默认。
 *
 * @return  boolen
 */
function virtual_card_shipping ($goods, $order_sn, &$msg, $process = 'other')
{
    /* 包含加密解密函数所在文件 */
    include_once(ROOT_PATH . 'includes/lib_code.php');

    /* 检查有没有缺货 */
    $sql = "SELECT COUNT(*) FROM ".$GLOBALS['ecs']->table('virtual_card')." WHERE goods_id = '$goods[goods_id]' AND is_saled = 0 ";
    $num = $GLOBALS['db']->GetOne($sql);

    if ($num < $goods['num'])
    {
        $msg .= sprintf($GLOBALS['_LANG']['virtual_card_oos'], $goods['goods_name']);

        return false;
    }

     /* 取出卡片信息 */
     $sql = "SELECT card_id, card_sn, card_password, end_date, crc32 FROM ".$GLOBALS['ecs']->table('virtual_card')." WHERE goods_id = '$goods[goods_id]' AND is_saled = 0  LIMIT " . $goods['num'];
     $arr = $GLOBALS['db']->getAll($sql);

     $card_ids = array();
     $cards = array();

     foreach ($arr as $virtual_card)
     {
        $card_info = array();

        /* 卡号和密码解密 */
        if ($virtual_card['crc32'] == 0 || $virtual_card['crc32'] == crc32(AUTH_KEY))
        {
            $card_info['card_sn'] = decrypt($virtual_card['card_sn']);
            $card_info['card_password'] = decrypt($virtual_card['card_password']);
        }
        elseif ($virtual_card['crc32'] == crc32(OLD_AUTH_KEY))
        {
            $card_info['card_sn'] = decrypt($virtual_card['card_sn'], OLD_AUTH_KEY);
            $card_info['card_password'] = decrypt($virtual_card['card_password'], OLD_AUTH_KEY);
        }
        else
        {
            $msg .= 'error key';

            return false;
        }
        $card_info['end_date'] = date($GLOBALS['_CFG']['date_format'], $virtual_card['end_date']);
        $card_ids[] = $virtual_card['card_id'];
        $cards[] = $card_info;
     }

     /* 标记已经取出的卡片 */
    $sql = "UPDATE ".$GLOBALS['ecs']->table('virtual_card')." SET ".
           "is_saled = 1 ,".
           "order_sn = '$order_sn' ".
           "WHERE " . db_create_in($card_ids, 'card_id');
    if (!$GLOBALS['db']->query($sql, 'SILENT'))
    {
        $msg .= $GLOBALS['db']->error();

        return false;
    }

    /* 更新库存 */
    $sql = "UPDATE ".$GLOBALS['ecs']->table('goods'). " SET goods_number = goods_number - '$goods[num]' WHERE goods_id = '$goods[goods_id]'";
    $GLOBALS['db']->query($sql);

    if (true)
    {
        /* 获取订单信息 */
        $sql = "SELECT order_id, order_sn, consignee, email FROM ".$GLOBALS['ecs']->table('order_info'). " WHERE order_sn = '$order_sn'";
        $order = $GLOBALS['db']->GetRow($sql);

        /* 更新订单信息 */
        if ($process == 'split')
        {
            $sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods'). "
                    SET send_number = send_number + '" . $goods['num'] . "'
                    WHERE order_id = '" . $order['order_id'] . "'
                    AND goods_id = '" . $goods['goods_id'] . "' ";
        }
        else
        {
            $sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods'). "
                    SET send_number = '" . $goods['num'] . "'
                    WHERE order_id = '" . $order['order_id'] . "'
                    AND goods_id = '" . $goods['goods_id'] . "' ";
        }

        if (!$GLOBALS['db']->query($sql, 'SILENT'))
        {
            $msg .= $GLOBALS['db']->error();

            return false;
        }
    }

    /* 发送邮件 */
    $GLOBALS['smarty']->assign('virtual_card',                   $cards);
    $GLOBALS['smarty']->assign('order',                          $order);
    $GLOBALS['smarty']->assign('goods',                          $goods);

    $GLOBALS['smarty']->assign('send_time', date('Y-m-d H:i:s'));
    $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
    $GLOBALS['smarty']->assign('send_date', date('Y-m-d'));
    $GLOBALS['smarty']->assign('sent_date', date('Y-m-d'));

    $tpl = get_mail_template('virtual_card');
    $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
    send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);

    return true;
}



/**
 *  虚拟商品
 * @param type $goods
 * @param type $order_sn
 * @param type $msg
 * @param type $process
 * @return boolean
 */
function virtual_goods_shipping ($goods, $order_sn, &$msg, $process = 'other')
{
/* 代码增加_虚拟团购_START  www.cfweb2015.com */
    $add_date = gmtime();
    for($i=0;$i<$goods['num'];$i++){
        $coded_card_sn   = rand(1000,9999). $i. $add_date;
        $end_date = $goods['valid_date'];
        $supplier_id = $goods['supplier_id'];
        $goods_attr_id = $goods['goods_attr_id'];
        $sql = "INSERT INTO ".$GLOBALS['ecs']->table('virtual_goods_card')." (goods_id, card_sn, end_date, add_date, supplier_id, is_verification) ".
                   "VALUES ('$goods[goods_id]', '$coded_card_sn', '$end_date', '$add_date', '$supplier_id', '0')";
        $GLOBALS['db']->query($sql);
    }

/* 代码增加_虚拟团购_END  www.cfweb2015.com */

 /* 取出卡片信息 */
     $sql = "SELECT card_id, card_sn, end_date,buy_date,supplier_id,is_verification  FROM ".$GLOBALS['ecs']->table('virtual_goods_card')." WHERE goods_id = '$goods[goods_id]' AND is_saled = 0  LIMIT " . $goods['num'];
     $arr = $GLOBALS['db']->getAll($sql);

     $card_ids = array();
     $cards = array();

     foreach ($arr as $virtual_card)
     {
        $card_info = array();
        $card_info['end_date'] = date($GLOBALS['_CFG']['date_format'], $virtual_card['end_date']);
        $card_ids[] = $virtual_card['card_id'];
        $cards[] = $card_info;
     }
     /* 标记已经取出的卡片 */
    $sql = "UPDATE ".$GLOBALS['ecs']->table('virtual_goods_card')." SET ".
           "is_saled = 1 ,".
           "order_sn = '$order_sn' ".
           "WHERE " . db_create_in($card_ids, 'card_id');
    if (!$GLOBALS['db']->query($sql, 'SILENT'))
    {
        $msg .= $GLOBALS['db']->error();

        return false;
    }

    /* 更新库存 */
    if(empty($goods_attr_id)){
        $sql = "UPDATE ".$GLOBALS['ecs']->table('goods'). " SET goods_number = goods_number - '$goods[num]' WHERE goods_id = '$goods[goods_id]'";
    }else{
        $goods_attr_id = str_replace(",","|",$goods_attr_id);
        $sql ="UPDATE ".$GLOBALS['ecs']->table('products')."set product_number = product_number - '$goods[num]'  where goods_id = '$goods[goods_id]' and goods_attr='$goods_attr_id'";
    }
    $GLOBALS['db']->query($sql);
    if (true)
    {
        /* 获取订单信息 */
        $sql = "SELECT order_id, order_sn, consignee, email FROM ".$GLOBALS['ecs']->table('order_info'). " WHERE order_sn = '$order_sn'";
        $order = $GLOBALS['db']->GetRow($sql);

        /* 更新订单信息 */
        if ($process == 'split')
        {
            $sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods'). "
                    SET send_number = send_number + '" . $goods['num'] . "'
                    WHERE order_id = '" . $order['order_id'] . "'
                    AND goods_id = '" . $goods['goods_id'] . "' ";
        }
        else
        {
            $sql = "UPDATE ".$GLOBALS['ecs']->table('order_goods'). "
                    SET send_number = '" . $goods['num'] . "'
                    WHERE order_id = '" . $order['order_id'] . "'
                    AND goods_id = '" . $goods['goods_id'] . "' ";
        }

        if (!$GLOBALS['db']->query($sql, 'SILENT'))
        {
            $msg .= $GLOBALS['db']->error();

            return false;
        }
        
    }

    /*发送手机验证码*/
//    require('send.php');
//    $mobile_phone = $goods['mobile_phone'];
//    foreach($arr as $v){
//        $content = '您的验证码：'.$v['card_sn'].', 请在 '.local_date('Y-m-d',$v['end_date']).' 之前使用';
//        sendSMS($mobile_phone,$content);
//    }
    
    /* 发送邮件 */
//    $GLOBALS['smarty']->assign('virtual_card',                   $cards);
//    $GLOBALS['smarty']->assign('order',                          $order);
//    $GLOBALS['smarty']->assign('goods',                          $goods);
//
//    $GLOBALS['smarty']->assign('send_time', date('Y-m-d H:i:s'));
//    $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
//    $GLOBALS['smarty']->assign('send_date', date('Y-m-d'));
//    $GLOBALS['smarty']->assign('sent_date', date('Y-m-d'));
//
//    $tpl = get_mail_template('virtual_card');
//    $content = $GLOBALS['smarty']->fetch('str:' . $tpl['template_content']);
//    send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
  return true;
}


/**
 *  返回虚拟卡信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function virtual_card_result($order_sn, $goods)
{
    /* 包含加密解密函数所在文件 */
    include_once(ROOT_PATH . 'includes/lib_code.php');

    /* 获取已经发送的卡片数据 */
    $sql = "SELECT card_sn, card_password, end_date, crc32 FROM ".$GLOBALS['ecs']->table('virtual_card')." WHERE goods_id= '$goods[goods_id]' AND order_sn = '$order_sn' ";
    $res= $GLOBALS['db']->query($sql);

    $cards = array();

    while ($row = $GLOBALS['db']->FetchRow($res))
    {
        /* 卡号和密码解密 */
        if ($row['crc32'] == 0 || $row['crc32'] == crc32(AUTH_KEY))
        {
            $row['card_sn'] = decrypt($row['card_sn']);
            $row['card_password'] = decrypt($row['card_password']);
        }
        elseif ($row['crc32'] == crc32(OLD_AUTH_KEY))
        {
            $row['card_sn'] = decrypt($row['card_sn'], OLD_AUTH_KEY);
            $row['card_password'] = decrypt($row['card_password'], OLD_AUTH_KEY);
        }
        else
        {
            $row['card_sn'] = '***';
            $row['card_password'] = '***';
        }

        $cards[] = array('card_sn'=>$row['card_sn'], 'card_password'=>$row['card_password'], 'end_date'=>date($GLOBALS['_CFG']['date_format'], $row['end_date']));
    }

    return $cards;
}



function virtual_goods_result($order_sn, $goods)
{

    /* 获取已经发送的卡片数据 */
    $sql = "SELECT card_sn,  end_date, is_verification FROM ".$GLOBALS['ecs']->table('virtual_goods_card')." WHERE goods_id= '$goods[goods_id]' AND order_sn = '$order_sn' ";
    $res= $GLOBALS['db']->query($sql);
    $cards = array();

    while ($row = $GLOBALS['db']->FetchRow($res))
    {       
        $cards[] = array('card_sn'=>$row['card_sn'], 'end_date'=>date($GLOBALS['_CFG']['date_format'], $row['end_date']),'is_verification'=>$row['is_verification']);
    }
    return $cards;
}
/**
 * 获取指定 id snatch 活动的结果
 *
 * @access  public
 * @param   int   $id       snatch_id
 *
 * @return  array           array(user_name, bie_price, bid_time, num)
 *                          num通常为1，如果为2表示有2个用户取到最小值，但结果只返回最早出价用户。
 */
function get_snatch_result($id)
{
    $sql = 'SELECT u.user_id, u.user_name, u.email, lg.bid_price, lg.bid_time, count(*) as num' .
            ' FROM ' . $GLOBALS['ecs']->table('snatch_log') . ' AS lg '.
            ' LEFT JOIN ' . $GLOBALS['ecs']->table('users') . ' AS u ON lg.user_id = u.user_id'.
            " WHERE lg.snatch_id = '$id'".
            ' GROUP BY lg.bid_price' .
            ' ORDER BY num ASC, lg.bid_price ASC, lg.bid_time ASC LIMIT 1';
    $rec = $GLOBALS['db']->GetRow($sql);

    if ($rec)
    {
        $rec['bid_time']  = local_date($GLOBALS['_CFG']['time_format'], $rec['bid_time']);
        $rec['formated_bid_price'] = price_format($rec['bid_price'], false);

        /* 活动信息 */
        $sql = 'SELECT ext_info " .
               " FROM ' . $GLOBALS['ecs']->table('goods_activity') .
               " WHERE act_id= '$id' AND act_type=" . GAT_SNATCH.
               " LIMIT 1";
        $row = $GLOBALS['db']->getOne($sql);
        $info = unserialize($row);

        if (!empty($info['max_price']))
        {
            $rec['buy_price'] = ($rec['bid_price'] > $info['max_price']) ? $info['max_price'] : $rec['bid_price'];
        }
        else
        {
            $rec['buy_price'] = $rec['bid_price'];
        }



        /* 检查订单 */
        $sql = "SELECT COUNT(*)" .
                " FROM " . $GLOBALS['ecs']->table('order_info') .
                " WHERE extension_code = 'snatch'" .
                " AND extension_id = '$id'" .
                " AND order_status " . db_create_in(array(OS_CONFIRMED, OS_UNCONFIRMED));

        $rec['order_count'] = $GLOBALS['db']->getOne($sql);
    }

    return $rec;
}

/**
 *  清除指定后缀的模板缓存或编译文件
 *
 * @access  public
 * @param  bool       $is_cache  是否清除缓存还是清出编译文件
 * @param  string     $ext       需要删除的文件名，不包含后缀
 *
 * @return int        返回清除的文件个数
 */
function clear_tpl_files($is_cache = true, $ext = '')
{
    $dirs = array();

    if (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)
    {
        $tmp_dir = DATA_DIR ;
    }
    else
    {
        $tmp_dir = 'temp';
    }
    if ($is_cache)
    {
        $cache_dir = ROOT_PATH . $tmp_dir . '/caches/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/query_caches/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/static_caches/';
        for($i = 0; $i < 16; $i++)
        {
            $hash_dir = $cache_dir . dechex($i);
            $dirs[] = $hash_dir . '/';
        }
    }
    else
    {
        $dirs[] = ROOT_PATH . $tmp_dir . '/compiled/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/compiled/admin/';
    }

    $str_len = strlen($ext);
    $count   = 0;

    foreach ($dirs AS $dir)
    {
        $folder = @opendir($dir);

        if ($folder === false)
        {
            continue;
        }

        while ($file = readdir($folder))
        {
            if ($file == '.' || $file == '..' || $file == 'index.htm' || $file == 'index.html')
            {
                continue;
            }
            if (is_file($dir . $file))
            {
                /* 如果有文件名则判断是否匹配 */
                $pos = ($is_cache) ? strrpos($file, '_') : strrpos($file, '.');

                if ($str_len > 0 && $pos !== false)
                {
                    $ext_str = substr($file, 0, $pos);

                    if ($ext_str == $ext)
                    {
                        if (@unlink($dir . $file))
                        {
                            $count++;
                        }
                    }
                }
                else
                {
                    if (@unlink($dir . $file))
                    {
                        $count++;
                    }
                }
            }
        }
        closedir($folder);
    }

    return $count;
}

/**
 *  清除手机指定后缀的模板缓存或编译文件
 *  wei2 增加 start by www.cfweb2015.com
 * @access  public
 * @param  bool       $is_cache  是否清除缓存还是清出编译文件
 * @param  string     $ext       需要删除的文件名，不包含后缀
 *
 * @return int        返回清除的文件个数
 */
function clear_tpl_files_mobile($is_cache = true, $ext = '')
{
    $dirs = array();

    if (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)
    {
        $tmp_dir = DATA_DIR ;
    }
    else
    {
        $tmp_dir = 'mobile/temp';
    }
    if ($is_cache)
    {
        $cache_dir = ROOT_PATH . $tmp_dir . '/caches/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/query_caches/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/static_caches/';
        for($i = 0; $i < 16; $i++)
        {
            $hash_dir = $cache_dir . dechex($i);
            $dirs[] = $hash_dir . '/';
        }
    }
    else
    {
        $dirs[] = ROOT_PATH . $tmp_dir . '/compiled/';
        $dirs[] = ROOT_PATH . $tmp_dir . '/compiled/admin/';
    }

    $str_len = strlen($ext);
    $count   = 0;

    foreach ($dirs AS $dir)
    {
        $folder = @opendir($dir);

        if ($folder === false)
        {
            continue;
        }

        while ($file = readdir($folder))
        {
            if ($file == '.' || $file == '..' || $file == 'index.htm' || $file == 'index.html')
            {
                continue;
            }
            if (is_file($dir . $file))
            {
                /* 如果有文件名则判断是否匹配 */
                $pos = ($is_cache) ? strrpos($file, '_') : strrpos($file, '.');

                if ($str_len > 0 && $pos !== false)
                {
                    $ext_str = substr($file, 0, $pos);

                    if ($ext_str == $ext)
                    {
                        if (@unlink($dir . $file))
                        {
                            $count++;
                        }
                    }
                }
                else
                {
                    if (@unlink($dir . $file))
                    {
                        $count++;
                    }
                }
            }
        }
        closedir($folder);
    }

    return $count;
}
/* wei2 增加 end by www.cfweb2015.com */
/**
 * 清除模版编译文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名， 不包含后缀
 * @return  void
 */
function clear_compiled_files($ext = '')
{
    return clear_tpl_files(false, $ext);
}

/**
 * 清除缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名， 不包含后缀
 * @return  void
 */
function clear_cache_files($ext = '')
{
    return clear_tpl_files(true, $ext);
}

/**
 * 清除手机缓存文件
 * wei2 增加 start by www.cfweb2015.com
 * @access  public
 * @param   mix     $ext    模版文件名， 不包含后缀
 * @return  void
 */
function clear_cache_files_mobile($ext = '')
{
    return clear_tpl_files_mobile(true, $ext);
}
/* wei2 增加 end by www.cfweb2015.com */
/**
 * 清除模版编译和缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名后缀
 * @return  void
 */
function clear_all_files($ext = '')
{
    return clear_tpl_files(false, $ext) + clear_tpl_files(true,  $ext);
}

/**
 * 清除手机模版编译和缓存文件
 *
 * @access  public
 * @param   mix     $ext    模版文件名后缀
 * @return  void
 */
function clear_all_files_mobile($ext = '')
{
    return clear_tpl_files_mobile(false, $ext) + clear_tpl_files_mobile(true,  $ext);
}
/* wei2 修改 end by www.cfweb2015.com */
/**
 * 页面上调用的js文件
 *
 * @access  public
 * @param   string      $files
 * @return  void
 */
function smarty_insert_scripts($args)
{
    static $scripts = array();

    $arr = explode(',', str_replace(' ','',$args['files']));

    $str = '';
    foreach ($arr AS $val)
    {
        if (in_array($val, $scripts) == false)
        {
            $scripts[] = $val;
            if ($val{0} == '.')
            {
                $str .= '<script type="text/javascript" src="' . $val . '"></script>';
            }
            else
            {
                $str .= '<script type="text/javascript" src="js/' . $val . '"></script>';
            }
        }
    }

    return $str;
}

/**
 * 创建分页的列表
 *
 * @access  public
 * @param   integer $count
 * @return  string
 */
function smarty_create_pages($params)
{
    extract($params);

    $str = '';
    $len = 10;

    if (empty($page))
    {
        $page = 1;
    }

    if (!empty($count))
    {
        $step = 1;
        $str .= "<option value='1'>1</option>";

        for ($i = 2; $i < $count; $i += $step)
        {
            $step = ($i >= $page + $len - 1 || $i <= $page - $len + 1) ? $len : 1;
            $str .= "<option value='$i'";
            $str .= $page == $i ? " selected='true'" : '';
            $str .= ">$i</option>";
        }

        if ($count > 1)
        {
            $str .= "<option value='$count'";
            $str .= $page == $count ? " selected='true'" : '';
            $str .= ">$count</option>";
        }
    }

    return $str;
}

/**
 * 重写 URL 地址
 *
 * @access  public
 * @param   string  $app        执行程序
 * @param   array   $params     参数数组
 * @param   string  $append     附加字串
 * @param   integer $page       页数
 * @param   string  $keywords   搜索关键词字符串
 * @return  void
 */
function build_uri($app, $params, $append = '', $page = 0, $keywords = '', $size = 0)
{
    static $rewrite = NULL;

    if ($rewrite === NULL)
    {
        $rewrite = intval($GLOBALS['_CFG']['rewrite']);
    }

    $args = array('go'	  => '',
                  'suppid'=> 0,
    			  'cid'   => 0,
                  'gid'   => 0,
                  'bid'   => 0,
                  'acid'  => 0,
                  'aid'   => 0,
                  'sid'   => 0,
                  'gbid'  => 0,
                  'auid'  => 0,
                  'sort'  => '',
                  'order' => '',
                );

    extract(array_merge($args, $params));

    $uri = '';
	switch ($app)
    {
    	case 'supplier':
    		$go = empty($go) ? 'index':$go;
            if ($go == 'category' || $go == 'index')
            {
                if ($rewrite)
                {
                   /* $uri = $app.'-'.$go.'-'.$suppid.'-' . $cid;
                    if (isset($bid))
                    {
                        $uri .= '-b' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '-min'.$price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '-max'.$price_max;
                    }
                    if (isset($filter_attr))
                    {
                        $uri .= '-attr' . $filter_attr;
                    }
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }*/
					$uri = $app.'.php?go='.$go.'&amp;suppId='.$suppid.'&amp;id=' . $cid;
                    if (!empty($bid))
                    {
                        $uri .= '&amp;brand=' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '&amp;price_min=' . $price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '&amp;price_max=' . $price_max;
                    }
                    if (!empty($filter_attr))
                    {
                        $uri .='&amp;filter_attr=' . $filter_attr;
                    }

                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                }
                else
                {
                    $uri = $app.'.php?go='.$go.'&amp;suppId='.$suppid.'&amp;id=' . $cid;
                    if (!empty($bid))
                    {
                        $uri .= '&amp;brand=' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '&amp;price_min=' . $price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '&amp;price_max=' . $price_max;
                    }
                    if (!empty($filter_attr))
                    {
                        $uri .='&amp;filter_attr=' . $filter_attr;
                    }

                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                }
            }
	    elseif ($go == 'article')
            {
                //$uri = $rewrite ? $app.'-article-'.$suppid.'-' . $aid : $app.'.php?go=article&suppId='.$suppid.'&id=' . $aid;
		$uri = $rewrite ? $app.'.php?go=article&suppId='.$suppid.'&id=' . $aid : $app.'.php?go=article&suppId='.$suppid.'&id=' . $aid;
            }
	    elseif($go == 'search')
            {
            	if ($rewrite)
                {
                   /* $uri = $app.'-'.$go.'-'.$suppid;
                	if (isset($cid))
                    {
                        $uri .= '-c' . $cid;
                    }
                    if (isset($bid))
                    {
                        $uri .= '-b' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '-min'.$price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '-max'.$price_max;
                    }
                    if (isset($filter_attr))
                    {
                        $uri .= '-attr' . $filter_attr;
                    }
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                	if (!empty($keywords))
                    {
                        $uri .= '-' . $keywords;
                    }*/
					$uri = $app.'.php?go='.$go.'&amp;suppId='.$suppid;
                	if (!empty($cid))
                    {
                        $uri .= '&amp;cid=' . $cid;
                    }
                    if (!empty($bid))
                    {
                        $uri .= '&amp;brand=' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '&amp;price_min=' . $price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '&amp;price_max=' . $price_max;
                    }
                    if (!empty($filter_attr))
                    {
                        $uri .='&amp;filter_attr=' . $filter_attr;
                    }

                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                	if (!empty($keywords))
                    {
                        $uri .= '&amp;keywords=' . $keywords;
                    }
                }
                else
                {
                    $uri = $app.'.php?go='.$go.'&amp;suppId='.$suppid;
                	if (!empty($cid))
                    {
                        $uri .= '&amp;cid=' . $cid;
                    }
                    if (!empty($bid))
                    {
                        $uri .= '&amp;brand=' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '&amp;price_min=' . $price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '&amp;price_max=' . $price_max;
                    }
                    if (!empty($filter_attr))
                    {
                        $uri .='&amp;filter_attr=' . $filter_attr;
                    }

                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                	if (!empty($keywords))
                    {
                        $uri .= '&amp;keywords=' . $keywords;
                    }
                }
            }

            break;
        case 'stores':
            if (empty($cid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'stores-' . $cid;
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                }
                else
                {
                    $uri = 'stores.php?id=' . $cid;
                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                }
            }

            break;
        case 'category':
            if (empty($cid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                   $uri = 'category-' . $cid;
                    if (isset($bid))
                    {
                        $uri .= '-b' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '-min'.$price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '-max'.$price_max;
                    }
                	if (isset($filter))
                    {
                        $uri .= '-fil' . $filter;
                    }
                    if (isset($filter_attr))
                    {
                        $uri .= '-attr' . $filter_attr;
                    }
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }	
					//$uri = get_dir('category', $cid). '/'.$uri;		
					//$uri = 'category.php?id=' . $cid;
//                    if (!empty($bid))
//                    {
//                        $uri .= '&amp;brand=' . $bid;
//                    }
//                    if (isset($price_min))
//                    {
//                        $uri .= '&amp;price_min=' . $price_min;
//                    }
//                    if (isset($price_max))
//                    {
//                        $uri .= '&amp;price_max=' . $price_max;
//                    }
//                	if (isset($filter))
//                    {
//                        $uri .= '&amp;filter=' . $filter;
//                    }
//                    if (!empty($filter_attr))
//                    {
//                        $uri .='&amp;filter_attr=' . $filter_attr;
//                    }
//
//                    if (!empty($page))
//                    {
//                        $uri .= '&amp;page=' . $page;
//                    }
//                    if (!empty($sort))
//                    {
//                        $uri .= '&amp;sort=' . $sort;
//                    }
//                    if (!empty($order))
//                    {
//                        $uri .= '&amp;order=' . $order;
//                    }
                }
                else
                {
                    $uri = 'category.php?id=' . $cid;
                    if (!empty($bid))
                    {
                        $uri .= '&amp;brand=' . $bid;
                    }
                    if (isset($price_min))
                    {
                        $uri .= '&amp;price_min=' . $price_min;
                    }
                    if (isset($price_max))
                    {
                        $uri .= '&amp;price_max=' . $price_max;
                    }
                	if (isset($filter))
                    {
                        $uri .= '&amp;filter=' . $filter;
                    }
                    if (!empty($filter_attr))
                    {
                        $uri .='&amp;filter_attr=' . $filter_attr;
                    }

                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                }
            }

            break;

        case 'goods':
            if (empty($gid))
            {
                return false;
            }
            else
            {
				if ($rewrite)
				{	
					$uri =  'goods-' . $gid;
					/*$pathrow = $GLOBALS['db']->getRow("select c.path_name,c.cat_id from ". $GLOBALS['ecs']->table('goods')." AS g left join ". $GLOBALS['ecs']->table('category') ." AS c on g.cat_id=c.cat_id where g.goods_id='$gid'" );
					$pathrow['path_name'] = $pathrow['path_name'] ? $pathrow['path_name'] : ("cat".$pathrow['cat_id']);
					$pathrow['path_name'] = PREFIX_CATEGORY ."-".$pathrow['path_name'];
					$uri = $pathrow['path_name']. '/'.$uri;*/
				}
				else
				{
					$uri = 'goods.php?id=' . $gid;
				}
            }

            break;
		case 'pre_sale':
            	if (empty($pre_sale_id))
            	{
            		return false;
            	}
            	else
            	{
            		if ($rewrite)
            		{
            			$uri = 'pre_sale-'.$pre_sale_id;
            		}
            		else
            		{
            			$uri = 'pre_sale.php?id=' . $pre_sale_id;
            		}
            	}
            
            	break;
        case 'brand':
            if (empty($bid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'brand-' . $bid;
                    if (isset($cid))
                    {
                        $uri .= '-c' . $cid;
                    }
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                }
                else
                {
                    $uri = 'brand.php?id=' . $bid;
                    if (!empty($cid))
                    {
                        $uri .= '&amp;cat=' . $cid;
                    }
                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                }
            }

            break;
        case 'article_cat':
            if (empty($acid))
            {
                return false;
            }
            else
            {
                if ($rewrite)
                {
                    $uri = 'article_cat-' . $acid;
                    if (!empty($page))
                    {
                        $uri .= '-' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '-' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '-' . $order;
                    }
                    if (!empty($keywords))
                    {
                        $uri .= '-' . $keywords;
                    }
					//$uri = get_dir('article_cat', $acid). '/'.$uri;
                }
                else
                {
                    $uri = 'article_cat.php?id=' . $acid;
                    if (!empty($page))
                    {
                        $uri .= '&amp;page=' . $page;
                    }
                    if (!empty($sort))
                    {
                        $uri .= '&amp;sort=' . $sort;
                    }
                    if (!empty($order))
                    {
                        $uri .= '&amp;order=' . $order;
                    }
                    if (!empty($keywords))
                    {
                        $uri .= '&amp;keywords=' . $keywords;
                    }
                }
            }

            break;
        case 'article':
            if (empty($aid))
            {
                return false;
            }
            else
            {
				if ($rewrite)
				{	
					$uri = 'article-' . $aid;
					/*$pathrow = $GLOBALS['db']->getRow("select c.path_name,c.cat_id from ". $GLOBALS['ecs']->table('article')." AS a left join ". $GLOBALS['ecs']->table('article_cat') ." AS c on a.cat_id=c.cat_id where a.article_id='$aid'" );
					$pathrow['path_name'] = $pathrow['path_name'] ? $pathrow['path_name'] : ("cat". $pathrow['cat_id']);
					$pathrow['path_name'] = PREFIX_ARTICLECAT ."-".$pathrow['path_name'];
					$uri = $pathrow['path_name']. '/'.$uri;*/
				}
				else
				{
					$uri = 'article.php?id=' . $aid;
				}
            }

            break;
        case 'group_buy':
            if (empty($gbid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'group_buy-' . $gbid : 'group_buy.php?act=view&amp;id=' . $gbid;
            }

            break;
        case 'auction':
            if (empty($auid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'auction-' . $auid : 'auction.php?act=view&amp;id=' . $auid;
            }

            break;
        case 'snatch':
            if (empty($sid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'snatch-' . $sid : 'snatch.php?id=' . $sid;
            }

            break;
		case 'pro_search':
            break;
        case 'search':
            break;
        case 'exchange':
            if ($rewrite)
            {
                $uri = 'exchange-' . $cid;
                if (isset($price_min))
                {
                    $uri .= '-min'.$price_min;
                }
                if (isset($price_max))
                {
                    $uri .= '-max'.$price_max;
                }
                if (!empty($page))
                {
                    $uri .= '-' . $page;
                }
                if (!empty($sort))
                {
                    $uri .= '-' . $sort;
                }
                if (!empty($order))
                {
                    $uri .= '-' . $order;
                }
            }
            else
            {
                $uri = 'exchange.php?cat_id=' . $cid;
                if (isset($price_min))
                {
                    $uri .= '&amp;integral_min=' . $price_min;
                }
                if (isset($price_max))
                {
                    $uri .= '&amp;integral_max=' . $price_max;
                }

                if (!empty($page))
                {
                    $uri .= '&amp;page=' . $page;
                }
                if (!empty($sort))
                {
                    $uri .= '&amp;sort=' . $sort;
                }
                if (!empty($order))
                {
                    $uri .= '&amp;order=' . $order;
                }
            }

            break;
        case 'exchange_goods':
            if (empty($gid))
            {
                return false;
            }
            else
            {
                $uri = $rewrite ? 'exchange-id' . $gid : 'exchange.php?id=' . $gid . '&amp;act=view';
            }

            break;
        default:
            return false;
            break;
    }

    if ($rewrite)
    {
        if ($rewrite == 2 && !empty($append))
        {
            $uri .= '-' . urlencode(preg_replace('/[\.|\/|\?|&|\+|\\\|\'|"|,]+/', '', $append));
        }
		if($app != 'supplier')
		{
        	$uri .= '.html';
		}
    }
    if (($rewrite == 2) && (strpos(strtolower(EC_CHARSET), 'utf') !== 0))
    {
        $uri = urlencode($uri);
    }
    return $uri;
}

/**
 * 格式化重量：小于1千克用克表示，否则用千克表示
 * @param   float   $weight     重量
 * @return  string  格式化后的重量
 */
function formated_weight($weight)
{
    $weight = round(floatval($weight), 3);
    if ($weight > 0)
    {
        if ($weight < 1)
        {
            /* 小于1千克，用克表示 */
            return intval($weight * 1000) . $GLOBALS['_LANG']['gram'];
        }
        else
        {
            /* 大于1千克，用千克表示 */
            return $weight . $GLOBALS['_LANG']['kilogram'];
        }
    }
    else
    {
        return 0;
    }
}

/**
 * 记录帐户变动
 * @param   int     $user_id        用户id
 * @param   float   $user_money     可用余额变动
 * @param   float   $frozen_money   冻结余额变动
 * @param   int     $rank_points    等级积分变动
 * @param   int     $pay_points     消费积分变动
 * @param   string  $change_desc    变动说明
 * @param   int     $change_type    变动类型：参见常量文件
 * @return  void
 */
function log_account_change($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER)
{
    /* 插入帐户变动记录 */
    $account_log = array(
        'user_id'       => $user_id,
        'user_money'    => $user_money,
        'frozen_money'  => $frozen_money,
        'rank_points'   => $rank_points,
        'pay_points'    => $pay_points,
        'change_time'   => gmtime(),
        'change_desc'   => $change_desc,
        'change_type'   => $change_type
    );
    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('account_log'), $account_log, 'INSERT');

    /* 更新用户信息 */
    $sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
            " SET user_money = user_money + ('$user_money')," .
            " frozen_money = frozen_money + ('$frozen_money')," .
            " rank_points = rank_points + ('$rank_points')," .
            " pay_points = pay_points + ('$pay_points')" .
            " WHERE user_id = '$user_id' LIMIT 1";
    $GLOBALS['db']->query($sql);
}

function log_account_change_minus($user_id, $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = ACT_OTHER)
{
    /* 插入帐户变动记录 */
    $account_log = array(
        'user_id'       => $user_id,
        'user_money'    => $user_money,
        'frozen_money'  => $frozen_money,
        'rank_points'   => $rank_points,
        'pay_points'    => $pay_points,
        'change_time'   => gmtime(),
        'change_desc'   => $change_desc,
        'change_type'   => $change_type
    );
    $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('account_log'), $account_log, 'INSERT');

    /* 更新用户信息 */
    $sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
            " SET user_money = user_money - ('$user_money')," .
            " frozen_money = frozen_money - ('$frozen_money')," .
            " rank_points = rank_points - ('$rank_points')," .
            " pay_points = pay_points - ('$pay_points')" .
            " WHERE user_id = '$user_id' LIMIT 1";
    $GLOBALS['db']->query($sql);
}
/**
 * 获得指定分类下的子分类的数组
 *
 * @access  public
 * @param   int     $cat_id     分类的ID
 * @param   int     $selected   当前选中分类的ID
 * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param   int     $level      限定返回的级数。为0时返回所有级数
 * @return  mix
 */
function article_cat_list($cat_id = 0, $selected = 0, $re_type = true, $level = 0)
{
    static $res = NULL;

    if ($res === NULL)
    {
        $data = read_static_cache('art_cat_pid_releate');
        if ($data === false)
        {
            $sql = "SELECT c.*, COUNT(s.cat_id) AS has_children, COUNT(a.article_id) AS aricle_num ".
               ' FROM ' . $GLOBALS['ecs']->table('article_cat') . " AS c".
               " LEFT JOIN " . $GLOBALS['ecs']->table('article_cat') . " AS s ON s.parent_id=c.cat_id".
               " LEFT JOIN " . $GLOBALS['ecs']->table('article') . " AS a ON a.cat_id=c.cat_id".
               " GROUP BY c.cat_id ".
               " ORDER BY parent_id, sort_order ASC";
            $res = $GLOBALS['db']->getAll($sql);
            write_static_cache('art_cat_pid_releate', $res);
        }
        else
        {
            $res = $data;
        }
    }

    if (empty($res) == true)
    {
        return $re_type ? '' : array();
    }

    $options = article_cat_options($cat_id, $res); // 获得指定分类下的子分类的数组

    /* 截取到指定的缩减级别 */
    if ($level > 0)
    {
        if ($cat_id == 0)
        {
            $end_level = $level;
        }
        else
        {
            $first_item = reset($options); // 获取第一个元素
            $end_level  = $first_item['level'] + $level;
        }

        /* 保留level小于end_level的部分 */
        foreach ($options AS $key => $val)
        {
            if ($val['level'] >= $end_level)
            {
                unset($options[$key]);
            }
        }
    }

    $pre_key = 0;
    foreach ($options AS $key => $value)
    {
        //$options[$key]['has_children'] = 1;
        if ($pre_key > 0)
        {
            if ($options[$pre_key]['cat_id'] == $options[$key]['parent_id'])
            {
                $options[$pre_key]['has_children'] = 1;
            }
        }
        $pre_key = $key;
    }

    if ($re_type == true)
    {
        $select = '';
        foreach ($options AS $var)
        {
            $select .= '<option value="' . $var['cat_id'] . '" ';
            $select .= ' cat_type="' . $var['cat_type'] . '" ';
            $select .= ($selected == $var['cat_id']) ? "selected='ture'" : '';
            $select .= '>';
            if ($var['level'] > 0)
            {
                $select .= str_repeat('&nbsp;', $var['level'] * 4);
            }
            $select .= htmlspecialchars(addslashes($var['cat_name'])) . '</option>';
        }

        return $select;
    }
    else
    {
        foreach ($options AS $key => $value)
        {
            $options[$key]['url'] = build_uri('article_cat', array('acid' => $value['cat_id']), $value['cat_name']);
        }
        return $options;
    }
}

/**
 * 过滤和排序所有文章分类，返回一个带有缩进级别的数组
 *
 * @access  private
 * @param   int     $cat_id     上级分类ID
 * @param   array   $arr        含有所有分类的数组
 * @param   int     $level      级别
 * @return  void
 */
function article_cat_options($spec_cat_id, $arr)
{
    static $cat_options = array();

    if (isset($cat_options[$spec_cat_id]))
    {
        return $cat_options[$spec_cat_id];
    }

    if (!isset($cat_options[0]))
    {
        $level = $last_cat_id = 0;
        $options = $cat_id_array = $level_array = array();
        while (!empty($arr))
        {
            foreach ($arr AS $key => $value)
            {
                $cat_id = $value['cat_id'];
                if ($level == 0 && $last_cat_id == 0)
                {
                    if ($value['parent_id'] > 0)
                    {
                        break;
                    }

                    $options[$cat_id]          = $value;
                    $options[$cat_id]['level'] = $level;
                    $options[$cat_id]['id']    = $cat_id;
                    $options[$cat_id]['name']  = $value['cat_name'];
                    unset($arr[$key]);

                    if ($value['has_children'] == 0)
                    {
                        continue;
                    }
                    $last_cat_id  = $cat_id;
                    $cat_id_array = array($cat_id);
                    $level_array[$last_cat_id] = ++$level;
                    continue;
                }

                if ($value['parent_id'] == $last_cat_id)
                {
                    $options[$cat_id]          = $value;
                    $options[$cat_id]['level'] = $level;
                    $options[$cat_id]['id']    = $cat_id;
                    $options[$cat_id]['name']  = $value['cat_name'];
                    unset($arr[$key]);

                    if ($value['has_children'] > 0)
                    {
                        if (end($cat_id_array) != $last_cat_id)
                        {
                            $cat_id_array[] = $last_cat_id;
                        }
                        $last_cat_id    = $cat_id;
                        $cat_id_array[] = $cat_id;
                        $level_array[$last_cat_id] = ++$level;
                    }
                }
                elseif ($value['parent_id'] > $last_cat_id)
                {
                    break;
                }
            }

            $count = count($cat_id_array);
            if ($count > 1)
            {
                $last_cat_id = array_pop($cat_id_array);
            }
            elseif ($count == 1)
            {
                if ($last_cat_id != end($cat_id_array))
                {
                    $last_cat_id = end($cat_id_array);
                }
                else
                {
                    $level = 0;
                    $last_cat_id = 0;
                    $cat_id_array = array();
                    continue;
                }
            }

            if ($last_cat_id && isset($level_array[$last_cat_id]))
            {
                $level = $level_array[$last_cat_id];
            }
            else
            {
                $level = 0;
            }
        }
        $cat_options[0] = $options;
    }
    else
    {
        $options = $cat_options[0];
    }

    if (!$spec_cat_id)
    {
        return $options;
    }
    else
    {
        if (empty($options[$spec_cat_id]))
        {
            return array();
        }

        $spec_cat_id_level = $options[$spec_cat_id]['level'];

        foreach ($options AS $key => $value)
        {
            if ($key != $spec_cat_id)
            {
                unset($options[$key]);
            }
            else
            {
                break;
            }
        }

        $spec_cat_id_array = array();
        foreach ($options AS $key => $value)
        {
            if (($spec_cat_id_level == $value['level'] && $value['cat_id'] != $spec_cat_id) ||
                ($spec_cat_id_level > $value['level']))
            {
                break;
            }
            else
            {
                $spec_cat_id_array[$key] = $value;
            }
        }
        $cat_options[$spec_cat_id] = $spec_cat_id_array;

        return $spec_cat_id_array;
    }
}

/**
 * 调用UCenter的函数
 *
 * @param   string  $func
 * @param   array   $params
 *
 * @return  mixed
 */
function uc_call($func, $params=null)
{
    restore_error_handler();
    if (!function_exists($func))
    {
        include_once(ROOT_PATH . 'uc_client/client.php');
    }

    $res = call_user_func_array($func, $params);

    set_error_handler('exception_handler');

    return $res;
}

/**
 * error_handle回调函数
 *
 * @return
 */
function exception_handler($errno, $errstr, $errfile, $errline)
{
    return;
}

/**
 * 重新获得商品图片与商品相册的地址
 *
 * @param int $goods_id 商品ID
 * @param string $image 原商品相册图片地址
 * @param boolean $thumb 是否为缩略图
 * @param string $call 调用方法(商品图片还是商品相册)
 * @param boolean $del 是否删除图片
 *
 * @return string   $url
 */
function get_image_path($goods_id, $image='', $thumb=false, $call='goods', $del=false)
{
    $url = empty($image) ? $GLOBALS['_CFG']['no_picture'] : $image;
    return $url;
}

/**
 * 调用使用UCenter插件时的函数
 *
 * @param   string  $func
 * @param   array   $params
 *
 * @return  mixed
 */
function user_uc_call($func, $params = null)
{
    if (isset($GLOBALS['_CFG']['integrate_code']) && $GLOBALS['_CFG']['integrate_code'] == 'ucenter')
    {
        restore_error_handler();
        if (!function_exists($func))
        {
            include_once(ROOT_PATH . 'includes/lib_uc.php');
        }

        $res = call_user_func_array($func, $params);

        set_error_handler('exception_handler');

        return $res;
    }
    else
    {
        return;
    }

}

/**
 * 取得商品优惠价格列表
 *
 * @param   string  $goods_id    商品编号
 * @param   string  $price_type  价格类别(0为全店优惠比率，1为商品优惠价格，2为分类优惠比率)
 *
 * @return  优惠价格列表
 */
function get_volume_price_list($goods_id, $price_type = '1')
{
    $volume_price = array();
    $temp_index   = '0';

    $sql = "SELECT `volume_number` , `volume_price`".
           " FROM " .$GLOBALS['ecs']->table('volume_price'). "".
           " WHERE `goods_id` = '" . $goods_id . "' AND `price_type` = '" . $price_type . "'".
           " ORDER BY `volume_number`";

    $res = $GLOBALS['db']->getAll($sql);

    foreach ($res as $k => $v)
    {
        $volume_price[$temp_index]                 = array();
        $volume_price[$temp_index]['number']       = $v['volume_number'];
        $volume_price[$temp_index]['price']        = $v['volume_price'];
        $volume_price[$temp_index]['format_price'] = price_format($v['volume_price']);
        $temp_index ++;
    }
    return $volume_price;
}

/**
 * 取得商品最终使用价格
 *
 * @param   string  $goods_id      商品编号
 * @param   string  $goods_num     购买数量
 * @param   boolean $is_spec_price 是否加入规格价格
 * @param   mix     $spec          规格ID的数组或者逗号分隔的字符串
 *
 * @return  商品最终购买价格
 */
function get_final_price($goods_id, $goods_num = '1', $is_spec_price = false, $spec = array())
{
    $final_price   = '0'; //商品最终购买价格
    $volume_price  = '0'; //商品优惠价格
    $promote_price = '0'; //商品促销价格
    $user_price    = '0'; //商品会员价格

    /* 判断商品是否参与预售活动，如果参与则获取商品 */
    if(!empty($_REQUEST['pre_sale_id']))
    {
    	$pre_sale = pre_sale_info($_REQUEST['pre_sale_id'], $goods_num);
    	if(!empty($pre_sale)){
    		$final_price = $pre_sale['cur_price'];
    		
    		//如果需要加入规格价格
    		if ($is_spec_price)
    		{
    			if (!empty($spec))
    			{
    				$spec_price   = spec_price($spec);
    				$final_price += $spec_price;
    			}
    		}
    		
    		return $final_price;
    	}
    }
    
    //取得商品优惠价格列表
    $price_list   = get_volume_price_list($goods_id, '1');

    if (!empty($price_list))
    {
        foreach ($price_list as $value)
        {
            if ($goods_num >= $value['number'])
            {
                $volume_price = $value['price'];
            }
        }
    }

	$discount = isset($GLOBALS['tongbu_user_discount']) ? $GLOBALS['tongbu_user_discount'] : $_SESSION['discount'];
	$user_rank = isset($GLOBALS['tongbu_user_rank']) ? $GLOBALS['tongbu_user_rank'] : $_SESSION['user_rank'];


    //取得商品促销价格列表
    /* 取得商品信息 */
    $sql = "SELECT g.promote_price, g.promote_start_date, g.promote_end_date, ".
                "IFNULL(mp.user_price, g.shop_price * '" . $discount . "') AS shop_price ".
           " FROM " .$GLOBALS['ecs']->table('goods'). " AS g ".
           " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                   "ON mp.goods_id = g.goods_id AND mp.user_rank = '" . $user_rank. "' ".
           " WHERE g.goods_id = '" . $goods_id . "'" .
           " AND g.is_delete = 0";
    $goods = $GLOBALS['db']->getRow($sql);

    /* 计算商品的促销价格 */
    if ($goods['promote_price'] > 0)
    {
        $promote_price = bargain_price($goods['promote_price'], $goods['promote_start_date'], $goods['promote_end_date']);
    }
    else
    {
        $promote_price = 0;
    }

    //取得商品会员价格列表
    $user_price    = $goods['shop_price'];

    //比较商品的促销价格，会员价格，优惠价格
    if (empty($volume_price) && empty($promote_price))
    {
        //如果优惠价格，促销价格都为空则取会员价格
        $final_price = $user_price;
    }
    elseif (!empty($volume_price) && empty($promote_price))
    {
        //如果优惠价格为空时不参加这个比较。
        $final_price = min($volume_price, $user_price);
    }
    elseif (empty($volume_price) && !empty($promote_price))
    {
        //如果促销价格为空时不参加这个比较。
        $final_price = min($promote_price, $user_price);
    }
    elseif (!empty($volume_price) && !empty($promote_price))
    {
        //取促销价格，会员价格，优惠价格最小值
        $final_price = min($volume_price, $promote_price, $user_price);
    }
    else
    {
        $final_price = $user_price;
    }

    //如果需要加入规格价格
    if ($is_spec_price)
    {
        if (!empty($spec))
        {
            $spec_price   = spec_price($spec);
            $final_price += $spec_price;
        }
    }

    //返回商品最终购买价格
    return $final_price;
}

/**
 * 将 goods_attr_id 的序列按照 attr_id 重新排序
 *
 * 注意：非规格属性的id会被排除
 *
 * @access      public
 * @param       array       $goods_attr_id_array        一维数组
 * @param       string      $sort                       序号：asc|desc，默认为：asc
 *
 * @return      string
 */
function sort_goods_attr_id_array($goods_attr_id_array, $sort = 'asc')
{
    if (empty($goods_attr_id_array))
    {
        return $goods_attr_id_array;
    }

    //重新排序
    $sql = "SELECT a.attr_type, v.attr_value, v.goods_attr_id
            FROM " .$GLOBALS['ecs']->table('attribute'). " AS a
            LEFT JOIN " .$GLOBALS['ecs']->table('goods_attr'). " AS v
                ON v.attr_id = a.attr_id
                AND a.attr_type = 1
            WHERE v.goods_attr_id " . db_create_in($goods_attr_id_array) . "
            ORDER BY a.attr_id $sort";
    $row = $GLOBALS['db']->GetAll($sql);

    $return_arr = array();
    foreach ($row as $value)
    {
        $return_arr['sort'][]   = $value['goods_attr_id'];

        $return_arr['row'][$value['goods_attr_id']]    = $value;
    }

    return $return_arr;
}

/**
 *
 * 是否存在规格
 *
 * @access      public
 * @param       array       $goods_attr_id_array        一维数组
 *
 * @return      string
 */
function is_spec($goods_attr_id_array, $sort = 'asc')
{
    if (empty($goods_attr_id_array))
    {
        return $goods_attr_id_array;
    }

    //重新排序
    $sql = "SELECT a.attr_type, v.attr_value, v.goods_attr_id
            FROM " .$GLOBALS['ecs']->table('attribute'). " AS a
            LEFT JOIN " .$GLOBALS['ecs']->table('goods_attr'). " AS v
                ON v.attr_id = a.attr_id
                AND a.attr_type = 1
            WHERE v.goods_attr_id " . db_create_in($goods_attr_id_array) . "
            ORDER BY a.attr_id $sort";
    $row = $GLOBALS['db']->GetAll($sql);

    $return_arr = array();
    foreach ($row as $value)
    {
        $return_arr['sort'][]   = $value['goods_attr_id'];

        $return_arr['row'][$value['goods_attr_id']]    = $value;
    }

    if(!empty($return_arr))
    {
        return true;
    }
    else
    {
        return false;
    }
}

/**
 * 获取指定id package 的信息
 *
 * @access  public
 * @param   int         $id         package_id
 *
 * @return array       array(package_id, package_name, goods_id,start_time, end_time, min_price, integral)
 */
function get_package_info($id)
{
    global $ecs, $db,$_CFG;
    $id = is_numeric($id)?intval($id):0;
    $now = gmtime();

    $sql = "SELECT act_id AS id,  act_name AS package_name, goods_id , goods_name, start_time, end_time, act_desc, ext_info".
           " FROM " . $GLOBALS['ecs']->table('goods_activity') .
           " WHERE act_id='$id' AND act_type = " . GAT_PACKAGE;

    $package = $db->GetRow($sql);

    /* 将时间转成可阅读格式 */
    if ($package['start_time'] <= $now && $package['end_time'] >= $now)
    {
        $package['is_on_sale'] = "1";
    }
    else
    {
        $package['is_on_sale'] = "0";
    }
    $package['start_time'] = local_date('Y-m-d H:i', $package['start_time']);
    $package['end_time']   = local_date('Y-m-d H:i', $package['end_time']);
    $row = unserialize($package['ext_info']);
    unset($package['ext_info']);
    if ($row)
    {
        foreach ($row as $key=>$val)
        {
            $package[$key] = $val;
        }
    }

    $sql = "SELECT pg.package_id, pg.goods_id, pg.goods_number, pg.admin_id, ".
           " g.goods_sn, g.goods_name, g.market_price, g.goods_thumb, g.is_real, ".
           " IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS rank_price " .
           " FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg ".
           "   LEFT JOIN ". $GLOBALS['ecs']->table('goods') . " AS g ".
           "   ON g.goods_id = pg.goods_id ".
           " LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
           " WHERE pg.package_id = " . $id. " ".
           " ORDER BY pg.package_id, pg.goods_id";

    $goods_res = $GLOBALS['db']->getAll($sql);

    $market_price        = 0;
    $real_goods_count    = 0;
    $virtual_goods_count = 0;

    foreach($goods_res as $key => $val)
    {
        $goods_res[$key]['goods_thumb']         = get_image_path($val['goods_id'], $val['goods_thumb'], true);
        $goods_res[$key]['market_price_format'] = price_format($val['market_price']);
        $goods_res[$key]['rank_price_format']   = price_format($val['rank_price']);
        $market_price += $val['market_price'] * $val['goods_number'];
        /* 统计实体商品和虚拟商品的个数 */
        if ($val['is_real'])
        {
            $real_goods_count++;
        }
        else
        {
            $virtual_goods_count++;
        }
    }

    if ($real_goods_count > 0)
    {
        $package['is_real']            = 1;
    }
    else
    {
        $package['is_real']            = 0;
    }

    $package['goods_list']            = $goods_res;
    $package['market_package']        = $market_price;
    $package['market_package_format'] = price_format($market_price);
    $package['package_price_format']  = price_format($package['package_price']);

    return $package;
}

/**
 * 获得商品的供应商信息
 * @param int $goods_id  商品id
 */
function get_product_supplier($goods_id){
	$sql = "SELECT s.supplier_name 
            FROM " . $GLOBALS['ecs']->table('goods') . " AS g
                LEFT JOIN " . $GLOBALS['ecs']->table('supplier') . " AS s ON s.supplier_id = g.supplier_id
            WHERE g.goods_id = '$goods_id' and g.supplier_id>0";
	$resource = $GLOBALS['db']->query($sql);
	$_row = $GLOBALS['db']->fetch_array($resource);
	if($_row){
		while($_row){
			return $_row['supplier_name'];
		}
	}else{
		return '网站自营';
	}
	
}

/**
 * 获得指定礼包的商品
 *
 * @access  public
 * @param   integer $package_id
 * @return  array
 */
/* 修改 by www.ecshop68.com 增加一个参数 */
function get_package_goods($package_id, $package_attr_id='')
{

	//增加 By www.ecshop68.com
	if ($package_attr_id)
	{
		$package_attr_id=str_replace(",", "','", $package_attr_id);
		$package_attr_id= "('" . $package_attr_id . "')";
		$sql_package_attr_id = " AND concat( pg.goods_id, '-' , pg.product_id ) in  $package_attr_id ";
	}
	 // 下面SQL语句增加两个字段，注意逗号 , g.goods_thumb, g.shop_price   By  www.cfweb2015.com
    $sql = "SELECT pg.goods_id, g.goods_name, pg.goods_number, p.goods_attr, p.product_number, p.product_id, g.goods_thumb, g.shop_price, ga.attr_price 
            FROM " . $GLOBALS['ecs']->table('package_goods') . " AS pg
                LEFT JOIN " .$GLOBALS['ecs']->table('goods') . " AS g ON pg.goods_id = g.goods_id
                LEFT JOIN " . $GLOBALS['ecs']->table('products') . " AS p ON pg.product_id = p.product_id
				LEFT JOIN " . $GLOBALS['ecs']->table('goods_attr') . " AS ga ON ga.goods_attr_id = p.goods_attr
            WHERE pg.package_id = '$package_id' $sql_package_attr_id "; //有修改 by www.ecshop68.com 注意最后那个 $sql_package_attr_id
    if ($package_id == 0)
    {
        $sql .= " AND pg.admin_id = '$_SESSION[admin_id]'";
    }
    $resource = $GLOBALS['db']->query($sql);
    if (!$resource)
    {
        return array();
    }

    $row = array();

    /* 生成结果数组 取存在货品的商品id 组合商品id与货品id */
    $good_product_str = '';
    while ($_row = $GLOBALS['db']->fetch_array($resource))
    {
        if ($_row['product_id'] > 0)
        {
            /* 取存商品id */
            $good_product_str .= ',' . $_row['goods_id'];

            /* 组合商品id与货品id */
            $_row['g_p'] = $_row['goods_id'] . '_' . $_row['product_id'];
        }
        else
        {
            /* 组合商品id与货品id */
            $_row['g_p'] = $_row['goods_id'];
        }
		
		/* 代码增加_start   By    www.cfweb2015.com */
		$_row['goods_thumb'] = get_image_path($_row['goods_id'], $_row['goods_thumb'], true);
        $_row['shop_price']    =   price_format($_row['shop_price']+$_row['attr_price']);
		/* 代码增加_end  By     www.cfweb2015.com */

        //生成结果数组
        $row[] = $_row;
    }
    $good_product_str = trim($good_product_str, ',');

    /* 释放空间 */
    unset($resource, $_row, $sql);

    /* 取商品属性 */
    if ($good_product_str != '')
    {
        $sql = "SELECT goods_attr_id, attr_value FROM " .$GLOBALS['ecs']->table('goods_attr'). " WHERE goods_id IN ($good_product_str)";
        $result_goods_attr = $GLOBALS['db']->getAll($sql);

        $_goods_attr = array();
        foreach ($result_goods_attr as $value)
        {
            $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
        }
    }

    /* 过滤货品 */
    $format[0] = '%s[%s]--[%d]';
    $format[1] = '%s--[%d]';
    foreach ($row as $key => $value)
    {
        if ($value['goods_attr'] != '')
        {
            $goods_attr_array = explode('|', $value['goods_attr']);

            $goods_attr = array();
            foreach ($goods_attr_array as $_attr)
            {
                $goods_attr[] = $_goods_attr[$_attr];
            }

            $row[$key]['goods_name'] = sprintf($format[0], $value['goods_name'], implode('，', $goods_attr), $value['goods_number']);
        }
        else
        {
            $row[$key]['goods_name'] = sprintf($format[1], $value['goods_name'], $value['goods_number']);
        }
    }

    return $row;
}

/**
 * 取商品的货品列表
 *
 * @param       mixed       $goods_id       单个商品id；多个商品id数组；以逗号分隔商品id字符串
 * @param       string      $conditions     sql条件
 *
 * @return  array
 */
function get_good_products($goods_id, $conditions = '')
{
    if (empty($goods_id))
    {
        return array();
    }

    switch (gettype($goods_id))
    {
        case 'integer':

            $_goods_id = "goods_id = '" . intval($goods_id) . "'";

        break;

        case 'string':
        case 'array':

            $_goods_id = db_create_in($goods_id, 'goods_id');

        break;
    }

    /* 取货品 */
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('products'). " WHERE $_goods_id $conditions";
    $result_products = $GLOBALS['db']->getAll($sql);

    /* 取商品属性 */
    $sql = "SELECT goods_attr_id, attr_value FROM " .$GLOBALS['ecs']->table('goods_attr'). " WHERE $_goods_id";
    $result_goods_attr = $GLOBALS['db']->getAll($sql);

    $_goods_attr = array();
    foreach ($result_goods_attr as $value)
    {
        $_goods_attr[$value['goods_attr_id']] = $value['attr_value'];
    }

    /* 过滤货品 */
    foreach ($result_products as $key => $value)
    {
        $goods_attr_array = explode('|', $value['goods_attr']);
        if (is_array($goods_attr_array))
        {
            $goods_attr = array();
            foreach ($goods_attr_array as $_attr)
            {
                $goods_attr[] = $_goods_attr[$_attr];
            }

            $goods_attr_str = implode('，', $goods_attr);
        }

        $result_products[$key]['goods_attr_str'] = $goods_attr_str;
    }

    return $result_products;
}

/**
 * 取商品的下拉框Select列表
 *
 * @param       int      $goods_id    商品id
 *
 * @return  array
 */
function get_good_products_select($goods_id)
{
    $return_array = array();
    $products = get_good_products($goods_id);

    if (empty($products))
    {
        return $return_array;
    }

    foreach ($products as $value)
    {
        $return_array[$value['product_id']] = $value['goods_attr_str'];
    }

    return $return_array;
}

/**
 * 取商品的规格列表
 *
 * @param       int      $goods_id    商品id
 * @param       string   $conditions  sql条件
 *
 * @return  array
 */
function get_specifications_list($goods_id, $conditions = '')
{
    /* 取商品属性 */
    $sql = "SELECT ga.goods_attr_id, ga.attr_id, ga.attr_value, a.attr_name
            FROM " .$GLOBALS['ecs']->table('goods_attr'). " AS ga, " .$GLOBALS['ecs']->table('attribute'). " AS a
            WHERE ga.attr_id = a.attr_id
            AND ga.goods_id = '$goods_id'
            $conditions";
    $result = $GLOBALS['db']->getAll($sql);

    $return_array = array();
    foreach ($result as $value)
    {
        $return_array[$value['goods_attr_id']] = $value;
    }

    return $return_array;
}

/**
 * 调用array_combine函数
 *
 * @param   array  $keys
 * @param   array  $values
 *
 * @return  $combined
 */
if (!function_exists('array_combine')) {
    function array_combine($keys, $values)
    {
        if (!is_array($keys)) {
            user_error('array_combine() expects parameter 1 to be array, ' .
                gettype($keys) . ' given', E_USER_WARNING);
            return;
        }

        if (!is_array($values)) {
            user_error('array_combine() expects parameter 2 to be array, ' .
                gettype($values) . ' given', E_USER_WARNING);
            return;
        }

        $key_count = count($keys);
        $value_count = count($values);
        if ($key_count !== $value_count) {
            user_error('array_combine() Both parameters should have equal number of elements', E_USER_WARNING);
            return false;
        }

        if ($key_count === 0 || $value_count === 0) {
            user_error('array_combine() Both parameters should have number of elements at least 0', E_USER_WARNING);
            return false;
        }

        $keys    = array_values($keys);
        $values  = array_values($values);

        $combined = array();
        for ($i = 0; $i < $key_count; $i++) {
            $combined[$keys[$i]] = $values[$i];
        }

        return $combined;
    }
}

/**
 * 获得指定省，市
 *
 */
function get_province_city($provinceid = 0, $cityid = 0)
{
	
	$provinceid = $provinceid?intval($provinceid):'0';
	$cityid = $cityid?intval($cityid):'0';
	
    $sql = 'SELECT region_name FROM ' . $GLOBALS['ecs']->table('region') .
            " WHERE region_id in (".$provinceid.",".$cityid.") order by region_id";
    $ret = $GLOBALS['db']->GetAll($sql);
    $dizhi = '';
    foreach($ret as $k => $v){
    	$dizhi .= $v['region_name'];
    }
    return $dizhi;
}
/* 代码增加_START  by  www.cfweb2015.com */
function gb2py($text, $exp = '')
{ 
	if(!$text) return '';
	if(EC_CHARSET != 'gbk') $text = ecs_iconv(EC_CHARSET, 'gbk', $text);
	$data = array();
	$tmp = @file(ROOT_PATH . 'includes/codetable/gb-pinyin.table');
	if(!$tmp) return '';
	$tmps = count($tmp);
	for($i = 0; $i < $tmps; $i++) {
		$tmp1 = explode("	", $tmp[$i]);
		$data[$i]=array($tmp1[0], $tmp1[1]);
	}
	$r = array();
	$k = 0;
	$textlen = strlen($text);
	for($i = 0; $i < $textlen; $i++) {
		$p = ord(substr($text, $i, 1));		
		if($p > 160) {
			$q = ord(substr($text, ++$i, 1));
			$p = $p*256+$q-65536;
		}
        if($p > 0 && $p < 160) {
            $r[$k] = chr($p);
        } elseif($p< -20319 || $p > -10247) {
            $r[$k] = '';
        } else {
            for($j = $tmps-1; $j >= 0; $j--) {
                if($data[$j][1]<=$p) break;
            }
            $r[$k] = $data[$j][0];
        }
		$k++;
	}
	return implode($exp, $r);
}

function Recordkeyword($word_www_68ecshop_com, $items = 0, $searchengine = 'ecshop')
{
	if(strlen($word_www_68ecshop_com) < 3 || strlen($word_www_68ecshop_com) > 30 || strpos($word_www_68ecshop_com, ' ') !== false) return;
	$sql_www_68ecshop_com = "SELECT * FROM " .$GLOBALS['ecs']->table('keyword'). " WHERE searchengine='ecshop' AND word='$word_www_68ecshop_com'";
	$r = $GLOBALS['db']->getRow($sql_www_68ecshop_com);
	if($r)
	{
		$items = intval($items) ;   //www.cfweb2015.com
		$month_search = date('Y-m', $r['updatetime']) == date('Y-m', gmtime()) ? 'month_search+1' : '1';
		$week_search = date('W', $r['updatetime']) == date('W', gmtime()) ? 'week_search+1' : '1';
		$today_search = date('Y-m-d', $r['updatetime']) == date('Y-m-d', gmtime()) ? 'today_search+1' : '1';
		
        $sql_www_68ecshop_com = "UPDATE " . $GLOBALS['ecs']->table('keyword') . " SET " .
                "items = '$items', " .
                "updatetime = '".gmtime()."', " .
                "total_search = total_search+1, " .
                "month_search = $month_search, " .
                "week_search = $week_search, " .
                "today_search = $today_search " .
				"WHERE w_id = '".$r['w_id']."'";
		$GLOBALS['db']->query($sql_www_68ecshop_com);
		$w_id = $r['w_id'];
	}
	else
	{
		$letter_www_68ecshop_com   = gb2py($word_www_68ecshop_com);
		$sql_www_68ecshop_com = "INSERT INTO " . $GLOBALS['ecs']->table('keyword') . " (searchengine, word, keyword, letter, items, updatetime, total_search, ".
		"month_search, week_search, today_search, status) " .
                " VALUES ('$searchengine', '$word_www_68ecshop_com', '$word_www_68ecshop_com', '$letter_www_68ecshop_com', '$items', '".gmtime()."', '1', '1', '1', '1', '1')";
        $GLOBALS['db']->query($sql_www_68ecshop_com);
		$w_id = $GLOBALS['db']->insert_id();
	}
	if (!empty($w_id))
	{
    	$ip_www_68ecshop_com       = real_ip();
    	$area_www_68ecshop_com     = ecs_geoip($ip);
    	$sql_www_68ecshop_com = 'INSERT INTO ' . $GLOBALS['ecs']->table('keyword_area') . ' ( ' .
                'w_id, access_time, ip_address, area) VALUES (' .
                "'$w_id', '".gmtime()."', '$ip_www_68ecshop_com', '$area_www_68ecshop_com')";
    	$GLOBALS['db']->query($sql_www_68ecshop_com);
	}
}
/* 代码增加_END    by   www.cfweb2015.com */
/**
 * 发短信方法
 * @param array $supplierinfo   eg: array('商家id1'=>订单号)
 * @param string $content 短信内容
 * @param int    $position 调用位置   1,下订单;2,付款
 * 
 */
function send_sms($supplierinfo='',$content='',$position=1){

	if(empty($supplierinfo) || empty($position)){
		return;
	}
	$supplier_ids = array_keys($supplierinfo);
	global $_CFG;

	if(array_search(0,$supplier_ids) !== false){
		if ($position == 1){
			if ($_CFG['sms_order_placed'] == '1' && $_CFG['sms_shop_mobile'] != '')
		    {
		    	$phones = explode(',',$_CFG['sms_shop_mobile']);
		    	array_filter($phones);
				$content1 = sprintf($content,$supplierinfo[0],$_CFG['sms_sign']);
		    	//$content1 = str_replace(array('ordersn','shopname'),array($supplierinfo[0],$_CFG['shop_name']),$content);

		    	foreach($phones as $phone){
		    		sendSMS($phone,$content1);
		    	}
		    }
		}elseif ($position == 2){
			if ($_CFG['sms_order_payed'] == '1' && $_CFG['sms_shop_mobile'] != '')
		    {
		    	$phones = explode(',',$_CFG['sms_shop_mobile']);
		    	array_filter($phones);
				$content1 = sprintf($content,$supplierinfo[0],$_CFG['sms_sign']);
		    	//$content1 = str_replace(array('ordersn','shopname'),array($supplierinfo[0],$_CFG['shop_name']),$content);
		    	foreach($phones as $phone){
		    		sendSMS($phone,$content1);
		    	}
		    }
		}
	    array_filter($supplier_ids);
	}

	foreach($supplier_ids as $val){
		
		$info = get_supplier_info($val);

		if ($position == 1){
			if ($info['sms_order_placed'] == '1' && $info['sms_shop_mobile'] != '')
		    {
		    	$phones = explode(',',$info['sms_shop_mobile']);
		    	array_filter($phones);
				$content1 = sprintf($content,$supplierinfo[$val],$_CFG['shop_name']);
		    	//$content1 = str_replace(array('ordersn','shopname'),array($supplierinfo[$val],$_CFG['shop_name']),$content);
		    	foreach($phones as $phone){
		    		sendSMS($phone,$content1);
		    	}
		    }
		}elseif ($position == 2){
			if ($info['sms_order_payed'] == '1' && $info['sms_shop_mobile'] != '')
		    {
		    	$phones = explode(',',$info['sms_shop_mobile']);
		    	array_filter($phones);
				$content1 = sprintf($content,$supplierinfo[$val],$_CFG['shop_name']);
		    	//$content1 = str_replace(array('ordersn','shopname'),array($supplierinfo[$val],$_CFG['shop_name']),$content);
		    	foreach($phones as $phone){
		    		sendSMS($phone,$content1);
		    	}
		    }
		}
	}
	
}

/**
 * 查询各个商家是否设置发短信功能，及收短信的手机号
 * @param int $suppid  商店id
 */
function get_supplier_info($suppid){
	$sql = "select code,value,supplier_id from ". $GLOBALS['ecs']->table('supplier_shop_config'). " where supplier_id=".$suppid." and parent_id=8";
 	$result = $GLOBALS['db']->getAll($sql);

    $return_array = array();
    foreach ($result as $value)
    {
        $return_array[$value['code']] = $value['value'];
    }
    return $return_array;
}
//推送到消息队列中 微信商城添加
function pushUserMsg($ecuid,$msg=array(),$type=1){
	$weixinconfig = $GLOBALS['db']->getRow ( "SELECT * FROM `weixin_config` WHERE `id` 

= 1" );

	if($type == 1 && $weixinconfig['buynotice'] == 1){

		$text = $weixinconfig['buymsg'];
	}elseif($type == 2 && $weixinconfig['sendnotice'] == 1){
		$text = $weixinconfig['sendmsg'];
		foreach($msg as $k=>$v){		
			$text = str_replace($k,$v,$text);
			$text = $text;
		}
	}else{
		return false;
	}	
	$user = $GLOBALS['db']->getRow("select * from weixin_user where ecuid='{$ecuid}'");
	if($user && $user['fake_id']){
		$content = array(
			'touser'=>$user['fake_id'],
			'msgtype'=>'text',
			'text'=>array('content'=>$text)
		);
		$content = serialize($content);
		$sendtime = $sendtime ? $sendtime : time();
		$createtime = time();
		$sql = "insert into weixin_corn 

(`ecuid`,`content`,`createtime`,`sendtime`,`issend`,`sendtype`) 
			value ({$ecuid},'{$content}','{$createtime}','{$sendtime}','0',

{$type})";
		$GLOBALS['db']->query($sql);
		return true;
	}else{
		return false;
	}
}
/* wei2 增加 end by www.cfweb2015.com */
/* 代码增加_start   By www.ecshop68.com */
function get_city_info($province, $city, $district)
{
	$sql = 'select region_id from ' . $GLOBALS['ecs']->table('region') . " where region_name='$province'";
	$province_id = $GLOBALS['db']->getOne($sql);
	
	if($province_id > 0)
	{
		$sql = 'select region_id from ' . $GLOBALS['ecs']->table('region') .
				"where parent_id=$province_id and region_name='$city'";
		$city_id = $GLOBALS['db']->getOne($sql);
		if($city_id > 0)
		{
			$sql = 'select region_id from ' . $GLOBALS['ecs']->table('region') .
					"where parent_id=$city_id and region_name='$district'";
			$district_id = $GLOBALS['db']->getOne($sql);
		}
	}
	return array('province_id' => $province_id, 'province' => $province, 'city_id' => $city_id, 'city' => $city,
				'district_id' => $district_id, 'district' => $district);
}
/* 代码增加_end   By www.ecshop68.com */

/* 代码增加_start   By     www.cfweb2015.com */
function GetPinyin($str, $ishead=0, $isclose=1)
{
    global $pinyins;
    $restr = '';
    $str = trim($str);
	if(EC_CHARSET != 'gbk')
	{
		$str = iconv(EC_CHARSET, 'gbk', $str);
	}
    $slen = strlen($str);
    if($slen < 2)
    {
        return $str;
    }
    if(count($pinyins) == 0)
    {
        $fp = fopen(ROOT_PATH.'includes/codetable/pinyin.dat', 'r');
        while(!feof($fp))
        {
            $line = trim(fgets($fp));
            $pinyins[$line[0].$line[1]] = substr($line, 3, strlen($line)-3);
        }
        fclose($fp);
    }
    for($i=0; $i<$slen; $i++)
    {
        if(ord($str[$i])>0x80)
        {
            $c = $str[$i].$str[$i+1];
            $i++;
            if(isset($pinyins[$c]))
            {
                if($ishead==0)
                {
                    $restr .= $pinyins[$c];
                }
                else
                {
                    $restr .= $pinyins[$c][0];
                }
            }else
            {
                $restr .= "_";
            }
        }else if( preg_match("/[a-z0-9]/i", $str[$i]) )
        {
            $restr .= $str[$i];
        }
        else
        {
            $restr .= "_";
        }
    }
    if($isclose==0)
    {
        unset($pinyins);
    }
    return $restr;
}
/* 代码增加_end   By     www.cfweb2015.com */

/* 代码增加_start  By  www.cfweb2015.com */

/*
* $page ： 生成哪个页面
* $id ： 商品详情页、文章详情页、专题详情页ID
* $cid：所属类别ID，如商品类别，文章类别，
*/
function make_html()
{
	return '';//停止所有的静态页面生成
	global $_CFG;
	
	if(intval($_CFG['rewrite'])<=0){
		return;
	}

	$out = ob_get_contents();	

	$thisurl= $GLOBALS['ecs']->get_domain().$_SERVER['REQUEST_URI'];
	$thisroot=$GLOBALS['ecs']->url();
	$makeurl = str_replace($thisroot, '', $thisurl);
	$makeurl = !empty($makeurl) ? $makeurl : "index.html"; //特殊值
	if(!strpos($makeurl, '.html'))
	{
		return false;
	}
	$makeurl = substr($makeurl, 0, strpos($makeurl, '.html')). ".html";
    $makepath = substr($makeurl, 0, strrpos($makeurl, '/'));
	$makepath_array =explode("/", $makepath);
	$dirname ="";
	foreach ($makepath_array AS $mkpath)
	{
		$dirname .=  $mkpath."/";
		if (!is_dir($dirname))
		{
			@mkdir($dirname, 0777);
			@chmod($dirname, 0777);
		}
	}
	
	if($makeurl)
	{
		@file_put_contents($makeurl,$out);
	}
	
}

/* 
* 删除某个HTML静态文件（商品详情、文章详情、）
* $cid 类别ID
* $id  商品ID，文章ID
*/

function clearhtml_file($type, $cid, $id)
{
	if ($type=='goods')
	{
		$dir = ROOT_PATH;// . get_dir('category', $cid);
		$file = $dir. "/goods-".$id.".html";
		@unlink($file);
	}
	elseif($type=='article')
	{
		$dir = ROOT_PATH;// . get_dir('article_cat', $cid);
		$file = $dir. "/article-".$id.".html";
		@unlink($file);
	}
	elseif($type=='index')
	{
		$dir = ROOT_PATH;
		$file = $dir. "index.html";
		@unlink($file);
	}
	elseif($type=='topic')
	{
		$dir = ROOT_PATH.'zhuanti';
		$file = $dir. "/topic-".$id.".html";
		@unlink($file);
	}
}

/*   删除某个dir下的所有HTML静态文件  $prefix  前缀  */
function clearhtml_dir($dir, $prefix="")
{
	$dir= $dir."/";
	$folder = @opendir($dir);
	if ($folder === false)
    {
         return false;
    }
    $str_len =strlen($prefix);
	while ($file = readdir($folder))
    {
            if ($file == '.' || $file == '..' || $file == 'index.htm' || $file == 'index.html')
            {
                continue;
            }
            if (is_file($dir . $file))
            {
                if ($str_len > 0)
                {
                    if (strpos($file, $prefix) !== false)
                    {
                        @unlink($dir . $file);
                    }
                }
                else
                {
                    @unlink($dir . $file);                   
                }
            }
     }
    closedir($folder);
}

/*
*  删除全站所有HTML静态文件
*/
function clearhtml_all()
{
	clearhtml_file('index', 0,0);
	$handle  = opendir(ROOT_PATH);
	$arr = array();
	while($file = readdir($handle))
	{
		$newpath = ROOT_PATH.$file;
		if(is_dir($newpath) && (strpos($newpath, PREFIX_CATEGORY) !==false || strpos($newpath, PREFIX_ARTICLECAT) !==false || strpos($newpath, PREFIX_TOPIC) !==false) ) 
		{
			$arr[] = $newpath ;
		}
	}
	foreach ($arr AS $dir)
	{
		clearhtml_dir($dir);
	}
}

/* 
* 获取保存生成HTML的目录名
*  $type：   category，article_cat
*  $id ：		类别ID
*  返回    前缀-path_name，不带 /
*/
function get_dir($type, $id)
{
	if (empty($id))
	{
		return false;
	}

	if ($type == 'category')
	{
		$sql = "select path_name, cat_id from ". $GLOBALS['ecs']->table('category') ."  where cat_id= '$id' ";
		$path_row = $GLOBALS['db'] ->getRow($sql);
		$path_row['path_name'] = $path_row['path_name'] ? $path_row['path_name'] : ("cat".$path_row['cat_id']);
		$path_row['path_name'] = PREFIX_CATEGORY."-".$path_row['path_name'];
	}
	elseif($type == 'article_cat')
	{
		$sql = "select path_name, cat_id from ". $GLOBALS['ecs']->table('article_cat') ." where cat_id= '$id' ";
		$path_row = $GLOBALS['db'] ->getRow($sql);
		$path_row['path_name'] = $path_row['path_name'] ? $path_row['path_name'] : "cat".$path_row['cat_id'];	
		$path_row['path_name'] = PREFIX_ARTICLECAT."-".$path_row['path_name'];
	}
	elseif($type == 'brand')
	{
		$path_row['path_name'] = "brand";
	}

	$dirname=trim($path_row['path_name']);
    
	/*
	if (!file_exists($dirname))
	{
		@mkdir($dirname, 0777);
		@chmod($dirname, 0777);
	}
	*/
	return $dirname;
 }

 /* 代码增加_end  By  www.cfweb2015.com ran.wang */
 
 function moneys_format($money,$type){
	if($type == 1){// 不四舍五入，保留1位
        $money = substr(number_format($money, 2, '.', ''), 0, -1);
	}else if($type == 2){// 不四舍五入，保留2位
        $money = substr(number_format($money, 3, '.', ''), 0, -1);
	}else if($type == 3){// 直接取整
        $money = intval($money);
    }else if($type == 4){// 四舍五入，保留 1 位
        $money = number_format($money, 1, '.', '');
    }else if($type == 5){// 先四舍五入，不保留小数
        $money = round($money);
    }
	
	return $money;
 }
 
 
/* 生成推广key */
/*
function create_expend_code($uid, $namespace = null) {  
    static $guid = '';  
    $uid = uniqid ($uid, true );  
      
    $data = $namespace;  
    $data .= $_SERVER ['REQUEST_TIME'];     // 请求那一刻的时间戳  
    $data .= $_SERVER ['HTTP_USER_AGENT'];  // 获取访问者在用什么操作系统  
    $data .= $_SERVER ['SERVER_ADDR'];      // 服务器IP  
    $data .= $_SERVER ['SERVER_PORT'];      // 端口号  
    $data .= $_SERVER ['REMOTE_ADDR'];      // 远程IP  
    $data .= $_SERVER ['REMOTE_PORT'];      // 端口信息  
      
    $hash = strtoupper ( hash ( 'ripemd128', $uid . $guid . md5 ( $data ) ) );  
    $guid = '{' . substr ( $hash, 0, 8 ) . '-' . substr ( $hash, 8, 4 ) . '-' . substr ( $hash, 12, 4 ) . '-' . substr ( $hash, 16, 4 ) . '-' . substr ( $hash, 20, 12 ) . '}';  
      
    return $guid;  
}  
*/


/**
 * 保存分销code
 *
 * @access  public
 * @param   void
 *
 * @return void
 * @author xuanyan
 **/
function set_fenxiao_code()
{
    $config = unserialize($GLOBALS['_CFG']['fenxiao']);
    if (!empty($_GET['ucode']) && $config['on'] == 1)
    {
        setcookie('fx_ucode', $_GET['ucode'], gmtime() + 3600 * 24 * 30); // 过期时间为 30 天
    }
}
/*** 分销函数 ***/
//设置推广信息
function set_user_base_expend_code($uid,$key,$value){
	$db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
    $sql = "update ".$ecs->table('user_expend')." set $key = '".$value."' where user_id = $uid limit 1";
    return $db->query($sql);
}
//获得推广的基本信息
function get_user_base_expend_code($uid){
	$db = $GLOBALS['db'];
    $ecs = $GLOBALS['ecs'];
	$arr = array();
    $sql = "SELECT * FROM " . $ecs->table('user_expend') . " WHERE user_id = $uid";
	//echo $sql;
	$row = $db->getRow($sql);
	//var_dump($row);
    if($row){
		$row['expend_amount_rest'] = price_format($row['expend_amount_rest']);
		$row['bonus_amount_format'] = price_format($row['bonus_amount']);
		$row['expend_amount_format'] = price_format($row['expend_amount']);
		$row['bonus_proportional_format'] = $row['bonus_proportional']."%";
        return $row;
    }
    return false;
}

function get_user_expend_code($uid){
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs'];
	$sql = "SELECT expend_code FROM " . $ecs->table('user_expend') . " WHERE user_id = $uid";
	//echo "--".$sql."<br>";
	$row = $db->getRow($sql);
	//var_dump($row);
	if($row && $row['expend_code']){
		return $row['expend_code'];
	}else{
		return create_expend_code($uid,'');
    }    
}
/* 生成推广key */
function create_expend_code($uid, $namespace = null) { 
	$db = $GLOBALS['db'];
	$ecs = $GLOBALS['ecs']; 
    static $guid = '';  
    $uid = uniqid ($uid, true );  
    //echo $uid;
    $data = $namespace;  
    $data .= $_SERVER ['REQUEST_TIME'];     // 请求那一刻的时间戳  
    $data .= $_SERVER ['HTTP_USER_AGENT'];  // 获取访问者在用什么操作系统  
    $data .= $_SERVER ['SERVER_ADDR'];      // 服务器IP  
    $data .= $_SERVER ['SERVER_PORT'];      // 端口号  
    $data .= $_SERVER ['REMOTE_ADDR'];      // 远程IP  
    $data .= $_SERVER ['REMOTE_PORT'];      // 端口信息  
      
    //$hash = strtoupper ( hash ( 'ripemd128', $uid . $guid . md5 ( $data ) ) );  
    //$guid = '' . substr ( $hash, 0, 8 ) . '-' . substr ( $hash, 8, 4 ) . '-' . substr ( $hash, 12, 4 ) . '-' . substr ( $hash, 16, 4 ) . '-' . substr ( $hash, 20, 12 ) . '';  
    $hash = strtoupper ( hash ( 'crc32', $uid . $guid . md5 ( $data ) ) );  
    $guid = substr($hash,0,8);
	//检测是否已存在
	$sql = "SELECT user_id FROM " . $ecs->table('user_expend') . " WHERE expend_code = '".$guid."'";
	//echo $sql;
	$row = $db->getRow($sql);
	if($row){
		return create_expend_code($uid,'');
	}else{
		return $guid;
	}
}  

/**
 * 获取分销code
 *
 * @access  public
 * @param   void
 *
 * @return int
 * @author xuanyan
 **/
function get_fenxiao_code()
{
    if (!empty($_COOKIE['fx_ucode']))
    {
        $ucode = $_COOKIE['fx_ucode'];
        $puid = $GLOBALS['db']->getOne('SELECT user_id FROM ' . $GLOBALS['ecs']->table('user_expend') . "WHERE expend_code = '$ucode'");
        if ($puid)
        {
            return $puid;
        }
        else
        {
            setcookie('fx_ucode', '', 1);
        }
    }

    return 0;
}

//插入分销关联
function save_expend_fenxiao_userinfo($username,$user_id){
	
	$expend_pid = get_fenxiao_code();
	
	$sql = "select user_id from ".$GLOBALS['ecs']->table('user_expend')." where user_id = $user_id ";
	$u = $GLOBALS['db']->getRow($sql);
	if($u){//已经注册的用户，则返回
		return false;
	}
	
	$sql = "insert into ".$GLOBALS['ecs']->table('user_expend')."(user_name,user_id, expend_pid,expend_code,expend_ctime)values("
			."'".$username."', '".$user_id."', '".$expend_pid."', '', '".time()."'"
			. ")";
	$GLOBALS['db']->query($sql);		

	return 1;
						  
}
//根据推广人数， 计算用户等级
function save_expend_user_info($id, $type, $usernum){
	set_user_bonus_level($id);
}
//完成订单时，更新有效人数
function set_user_bonus_level($id){
	if(!$id){ return 0;}
	$fenxiao = unserialize($GLOBALS['_CFG']['fenxiao']);
	if($fenxiao['on'] != 1){ return false;}
	
	$levellist = $fenxiao['dataitem'];
	foreach($levellist as $k=>$v){
		$$k = $v;
			
	}
	$zhitui_leve0_num = $zhitui_leve0['sbs0_share_people_num'];
	$zhitui_leve0_l1 =  $zhitui_leve0['sbs0_leve1'];
	
	
	$zhitui_leve1_num = $zhitui_leve1['sbs1_share_people_num'];
	
	$zhitui_leve2_num = $zhitui_leve2['sbs2_share_people_num'];
	
	$zhitui_leve3_num = $zhitui_leve3['sbs3_share_people_num'];
	
	$zhitui_leve4_num = $zhitui_leve4['sbs4_share_people_num'];
	
	$zhitui_leve5_num = $zhitui_leve5['sbs5_share_people_num'];

	// 计算有效的总推广人数 
	$sql = "select count(id) from ".$GLOBALS['ecs']->table('user_expend')." where expend_pid = $id and is_valid_user = 1";
	$user_amount = $GLOBALS['db']->getOne($sql);
	$user_amount = intval($user_amount);
	
	//更新有效人数
	$sql = "update ".$GLOBALS['ecs']->table('user_expend')." set expend_user_amount = ".$user_amount." where user_id = $id";
	$GLOBALS['db']->query($sql);
		
	$bonus = 0;
	if($user_amount >= $zhitui_leve5_num){ //达到56人
		$bonus = 5;
	}else if($user_amount >= $zhitui_leve4_num){ //2%分红（直接邀请有效人数达到32人，直接购买E款500000的产品）
		$bonus = 2;
	}else if($user_amount >= $zhitui_leve3_num){ //1%分红(直接邀请有效人数达到18人，直接购买D款200000的产品)
		$bonus = 1;
	}else if($user_amount >= $zhitui_leve2_num){
	}else if($user_amount >= $zhitui_leve1_num){
	}else if($user_amount >= $zhitui_leve0_num){}
	//更新分红
	if($bonus){
		$sql = "update ".$GLOBALS['ecs']->table('user_expend')." set is_bonus = 1, bonus_proportional = '".$bonus."' where user_id = $id";
		$GLOBALS['db']->query($sql);
	}
}

//确认订单后，设置为有效用户
function set_user_to_fenxiao($userid){

	$sql =" select is_valid_user,expend_pid from ".$GLOBALS['ecs']->table('user_expend')." where user_id = $userid "; //设置当前用户为有效用户
	$is_fenxiao = $GLOBALS['db']->getRow($sql);
	if($is_fenxiao){
		$expend_pid = $is_fenxiao['expend_pid'];
		if($is_fenxiao['is_valid_user'] == '0'){
			$sql ="update ".$GLOBALS['ecs']->table('user_expend')." set is_valid_user = 1 where user_id = $userid ";
			$GLOBALS['db']->query($sql);
		}
	}else{
		$expend_pid = get_fenxiao_code();
		$sql = "insert into ".$GLOBALS['ecs']->table('user_expend')." (user_id, expend_pid, is_valid_user) values($userid, $expend_pid, 1)";
		$GLOBALS['db']->query($sql);
	}
	if($expend_pid){
		set_user_bonus_level($expend_pid);
	}
	return 1;
}



/* 计算此用户消费总金额，看是否达到分红级别 */
/*
function check_user_fenhong_status($user_id){
	$ordersql = "select sum(goods_amount) from ".$GLOBALS['ecs']->table('order_info')." where user_id = $user_id and shipping_status = '2'";
	$total_money = $GLOBALS['db']->getOne($ordersql);
	
	$sql =" select is_bonus from ".$GLOBALS['ecs']->table('user_expend')." where user_id = $user_id ";
	$is_bonus = $GLOBALS['db']->getOne($sql);
	//设置用户分红等级
	if(!$is_bonus){
		$level = 0;
		if($total_money >= 200000){
			$level = 1;
		}else if($total_money >= 500000){
			$level = 2;
		}
		
		if($level){
			$sql ="update ".$GLOBALS['ecs']->table('user_expend')." set bonus_proportional = $level,is_bonus = 1,is_valid_user = 1 where user_id = $user_id ";
			$GLOBALS['db']->query($sql);
			
			set_field_change_log($user_id,"user_expend.bonus_proportional", '',$level);
		}
	}
	
	
}
*/

function check_user_fenhong_status($user_id,$oid){
	if($oid){
		$sql = "select goods_amount from ".$GLOBALS['ecs']->table('order_info')." where order_id = $oid ";
		$total_money = $GLOBALS['db']->getOne($sql);
	}else{
			//按照用户总消费额，来判断分红比例
			$ordersql = "select sum(goods_amount) from ".$GLOBALS['ecs']->table('order_info')." where user_id = $user_id and shipping_status = '2'";
			$total_money = $GLOBALS['db']->getOne($ordersql);
	}
	$total_money = floatval($total_money);
	$sql =" select is_bonus from ".$GLOBALS['ecs']->table('user_expend')." where user_id = $user_id ";
	$is_bonus = $GLOBALS['db']->getOne($sql);
	
	$level = 0;
	if($total_money >= 500000){
		$level = 2;
	}else if($total_money >= 200000){
		$level = 1;
	}
	
	//设置用户分红等级
	
	if($level){
		$sql ="update ".$GLOBALS['ecs']->table('user_expend')." set bonus_proportional = $level,is_bonus = 1,is_valid_user = 1 where user_id = $user_id ";
		$GLOBALS['db']->query($sql);
	
		set_field_change_log($user_id,$total_money."]user_expend.bonus_proportional--".$sql, '',$level);
		//set_field_change_log($user_id,"user_expend.bonus_proportional", '',$level);
	}
}

//分佣
function expend_order_log($order_id, $user_id){
    $fenxiao = unserialize($GLOBALS['_CFG']['fenxiao']);
    $sql = "SELECT order_id,order_sn,user_id,order_status,goods_amount,order_amount FROM " . $GLOBALS['ecs']->table('order_info') . " WHERE order_id = $order_id ";
    $order = $GLOBALS['db']->getRow($sql);
    
    //只对产品价格进行分佣
    if($order && $order['goods_amount']){
        $amount = $order['goods_amount'];
        //第一级分销
        $epuid_l1 = get_expend_puser_info($user_id);
        //var_dump($epuid_l1);
        if($epuid_l1 && $epuid_l1['uid']){ //达到分销级别了，计算分佣
            $cur1 = $fenxiao['dataitem']['zhitui_leve'.$epuid_l1['level']]["sbs".$epuid_l1['level']."_leve1"];
            //echo $cur1;
            save_ecs_user_expend_log($user_id,$epuid_l1['uid'],$epuid_l1['level'], $cur1,$order);
        }
        
        //第二级分销
        if($epuid_l1){
            $epuid_l2 = get_expend_puser_info($epuid_l1['uid']);
            if($epuid_l2 && $epuid_l2['uid']){
                $cur2 = $fenxiao['dataitem']['zhitui_leve'.$epuid_l2['level']]["sbs".$epuid_l2['level']."_leve2"];
                 save_ecs_user_expend_log($user_id,$epuid_l2['uid'],$epuid_l2['level'], $cur2,$order);
            }
        }else{
            $epuid_l2 = 0;
        }
        //第三级分销
        if($epuid_l2){
            $epuid_l3 = get_expend_puser_info($epuid_l2['uid']);
            if($epuid_l3 && $epuid_l3['uid']){
                $cur3 = $fenxiao['dataitem']['zhitui_leve'.$epuid_l3['level']]["sbs".$epuid_l3['level']."_leve3"];
                 save_ecs_user_expend_log($user_id,$epuid_l3['uid'],$epuid_l3['level'], $cur3,$order);
            }
            
        }else{
            $epuid_l3 = 0;
        }
    }
    return false;
}

//设置到账分佣
function set_fx_cash($user_id,$k,$f,$v){
    $sql = "update ".$GLOBALS['ecs']->table('user_expend')." set $f = $f $k $v where user_id = $user_id";
    //echo $sql;
    $GLOBALS['db']->query($sql);
	
	$sql = "insert into ".$GLOBALS['ecs']->table('fieldlog')."(user_id,f,k,v,time) values(".
			"'".$user_id."',".
			"'".$f."',".
			"'".$k."',".
			"'".$v."',".
			"'".time()."'".
			")";
	$GLOBALS['db']->query($sql);		
}
function get_cash_rest($user_id){
    $sql = "select sum(expend_amount) from ".$GLOBALS['ecs']->table('user_expend_log')." where status = 0 and expend_user_id = $user_id";
    //echo $sql;
    $amount_rest = $GLOBALS['db']->getOne($sql);
    return $amount_rest;
}
//获得已提现分佣
function get_cash_tixian(){
    
}

function save_ecs_user_expend_log($userid,$expend_user_id,$level, $bili,$order){
    $expend_amount = $order['goods_amount']*($bili/100);
    $checksql = "select id from ".$GLOBALS['ecs']->table('user_expend_log')." where expend_user_id = $expend_user_id and order_id = ".$order['order_id']." limit 1";
    $check = $GLOBALS['db']->getOne($checksql);
    //var_dump($check);
    if(!$check){ //检测是否此订单已给此用户分佣
        $sql = "insert into ".$GLOBALS['ecs']->table('user_expend_log')."(user_id, order_id,expend_level,expend_user_id,order_amount,expend_amount,status,ctime, expend_bili) values(".
                "'".$userid."',".
                "'".$order['order_id']."',".
                "'".$level."',".
                "'".$expend_user_id."',".
                "'".$order['goods_amount']."',".
                "'".$expend_amount."',".
                "'0',".
                "'".time()."',".
                "'".$bili."'".
                ")";
        //echo "--->".$sql."<br>";
        $GLOBALS['db']->query($sql);
		common_push_expend_user($expend_user_id, $order['order_id']);
    }else{
        return true; //已分佣
    }
    
}

function common_push_expend_user($uid, $oid){
	$id = intval($id);
	$sql = "select id, user_id, order_id, expend_user_id, order_amount, expend_amount, status ,ctime from ".$GLOBALS['ecs']->table('user_expend_log')." where order_id = $oid and expend_user_id = $uid ";
	//echo $sql."<br>";
	$info = $GLOBALS['db']->getRow($sql);
	if(!$info){ return 0;}
	$id = $info['id'];
	$userid = $info['expend_user_id'];
	$expend_amount = $info['expend_amount'];
	if($info['status'] == 1){ return 0;  }
	
	$sql = "update ".$GLOBALS['ecs']->table('user_expend_log')." set status = 1 where id = $id ";
	//echo $sql."<br>";
	$GLOBALS['db']->query($sql);
	
	//到账
	//累计到用户资金， 用户分佣金额
	set_fx_cash($userid,"+","expend_amount",$expend_amount);//累计用户分佣金额
			
	//佣金到账后，增加用户资金
	log_account_change($userid, $expend_amount, 0, 0, 0, '佣金到账，佣金id :'.$id, 3);
	return 1;
}
function get_expend_puid($uid){
    $puid = $GLOBALS['db']->getOne('SELECT expend_pid FROM ' . $GLOBALS['ecs']->table('user_expend') . "WHERE user_id = '$uid'");
    if ($puid)
    {
        return $puid;
    }
    return 0;
}
function get_expend_puser_info($id){
    $fenxiao = unserialize($GLOBALS['_CFG']['fenxiao']);
    $uid = get_expend_puid($id);
    $amount = $GLOBALS['db']->getOne('SELECT expend_user_amount FROM ' . $GLOBALS['ecs']->table('user_expend') . "WHERE user_id = '$uid'");
  
    if ($fenxiao)
    {
        $level = 0;
        if($fenxiao['dataitem']){
            foreach($fenxiao['dataitem'] as $k=>$v){
                $i = str_replace("zhitui_leve", '', $k);
                //echo $k."----".$v['sbs'.$i.'_share_people_num']."<br>";
                $$k = $v['sbs'.$i.'_share_people_num'];
                
            }
            
            if($amount>=$zhitui_leve5){
                $level = 5;
            }else if($amount>= $zhitui_leve4 && $amount<$zhitui_leve5){
                $level = 4;
            }else if($amount>= $zhitui_leve3 && $amount<$zhitui_leve4){
                $level = 3;
            }else if($amount>= $zhitui_leve2 && $amount<$zhitui_leve3){
                $level = 2;
            }else if($amount>= $zhitui_leve1 && $amount<$zhitui_leve2){
                $level = 1;
            }else if($amount>= $zhitui_leve0 && $amount<$zhitui_leve1){
                $level = 0;
            }
        }
        return array("uid"=>$uid,"level"=>$level);
    }
    return 0;
}
function put_tixian($user_id, $tixian_money,$tixian_type,$tixian_account,$shijidaozhang,$shouxufei){
	$checksql = "select user_id, money from ".$GLOBALS['ecs']->table('fxamount_log')." where user_id = $user_id and status = '0'";
	$check = $GLOBALS['db']->getRow($checksql);
	
	if($check){
		return 0;
	}else{
		
		$sql = "insert into ".$GLOBALS['ecs']->table("fxamount_log")."(user_id, money, type, account, status,ctime,shijidaozhang,shouxufei) values(".
		"'".$user_id."',".
		"'".$tixian_money."',".
		"'".$tixian_type."',".
		"'".$tixian_account."',".
		"'0',".
		"'".time()."',".
		"'".$shijidaozhang."',".
		"'".$shouxufei."'".
		")";
		$flag = $GLOBALS['db']->query($sql);
		
		if($flag){
			//set_fx_cash($user_id,"+","expend_amount_cash",$tixian_money);//累计提现金额
			//set_fx_cash($user_id,"-","expend_amount",$tixian_money);//减去总佣金
			
			//保存用户资金变动,提交提现后， 将总金额减少， 等待管理员确认，管理员确认后，则修改fxamount_log状态
			log_account_change_minus($user_id, $tixian_money, 0, 0, 0, "提现，减少资金", 5);
		}
		
		return true;
	}
}
function get_tixian_list($user_id,$num){
	
	$sql = "select id,user_id,money,type,account,ctime,shijidaozhang,shouxufei,status from ".$GLOBALS['ecs']->table('fxamount_log')." where user_id = $user_id order by ctime desc limit $num";
	$data = $GLOBALS['db']->getAll($sql);
	if($data){
		foreach($data as $k=>&$v){
			$v['status'] = $v['status']==1?"已完成":"未审核";
			$v['ctime'] = date("Y-m-d H:i",$v['ctime']);
		}
	}
	return $data;
}

function get_bonus_list($user_id,$num){
	$sql = "select id,user_id,user_bonus_level,bonus_money,ctime,bonus_id from ".$GLOBALS['ecs']->table('user_bonus_log')." where user_id = $user_id and status = 1 order by ctime desc limit $num";
	$data = $GLOBALS['db']->getAll($sql);
	if($data){
		foreach($data as $k=>&$v){
			$v['ctime'] = date("Y-m-d H:i",$v['ctime']);
			$v['user_bonus_level'] = $v['user_bonus_level']."%";
		}
	}
	return $data;
}

function get_yongjin_list($user_id,$num){
	$sql = "select id,user_id,order_id,expend_level, expend_user_id, expend_bili,order_amount, expend_amount, status, ctime from ".$GLOBALS['ecs']->table('user_expend_log')." where expend_user_id = $user_id order by ctime desc limit $num";
	$data = $GLOBALS['db']->getAll($sql);
	if($data){
		foreach($data as $k=>&$row){
			$row['order_amount'] = price_format(abs($row['order_amount']), false);
			$row['expend_amount'] = price_format(abs($row['expend_amount']), false);
			$row['ctime'] = date("Y-m-d H:i",$row['ctime']);
			if($row['status'] == 0){
				$row['status_label'] = "未审核";
			}else if($row['status'] == 1){
				$row['status_label'] = "已到账";
			}else if($row['status'] == 3){
				$row['status_label'] = "撤销";
			}else if($row['status'] == 4){
				$row['status_label'] = "无效";
			}
		}
	}
	return $data;
}
function set_field_change_log($user_id,$f,$k,$v){
	$sql = "insert into ".$GLOBALS['ecs']->table('fieldlog')."(user_id,f,k,v,time) values(".
			"'".$user_id."',".
			"'".$f."',".
			"'".$k."',".
			"'".$v."',".
			"'".time()."'".
			")";
	$GLOBALS['db']->query($sql);
}	


/******************** 365 返现 *********************/
/*
** 订单支付成功后调用此方法，执行以下操作
** 查询客户是否是返现用户
** 累计客户购物金额总金额
** 更新客户返现标识
**
*/
function user_back_point($user_id,$order_amount){
	//$order_amount = number_format(floatval($order_amount), 2, '.', '');
	echo "<br>================= user back point function =================<br>";
	//更新用户现金支付表， 现金交易总额，以及users表，is_back_point
	$sql = "select id, user_id, consume, back_point from ".$GLOBALS['ecs']->table('user_backpoint')." where user_id = $user_id ";
	$userinfo = $GLOBALS['db']->getRow($sql);
	
	if($userinfo && isset($userinfo['user_id'])){
		$sql = "update ".$GLOBALS['ecs']->table('user_backpoint')." set consume = consume + ".$order_amount." ,utime = ".intval(time())." where user_id = $user_id ";
	}else{
		$sql = " insert into ".$GLOBALS['ecs']->table('user_backpoint')."( user_id, consume, back_point, ctime, utime) values("
			."'".$user_id."',"
			."'".$order_amount."',"
			."0,"
			."".intval(time()).","
			.intval(time())
		.")";
	}
	printsqls($sql);
	
	$GLOBALS['db']->query($sql);
	
	if(check_user_is_fanxian($user_id)){
		$user_sql = "update ".$GLOBALS['ecs']->table('users')." set is_back_point = 1 where  user_id = $user_id limit 1 ";
		printsqls($user_sql);
		$GLOBALS['db']->query($user_sql);
	}else{
		$user_sql = "update ".$GLOBALS['ecs']->table('users')." set is_back_point = 0 where  user_id = $user_id limit 1 ";
		printsqls($user_sql);
		$GLOBALS['db']->query($user_sql);
	}
	
	
	
	

}
/*
** 检查用户是否符合返现需求
** 第一种方式 jinefanxian ： 达到jishu钱，可以返现， 只要购物超过jishu， 从此一直享受返现， 以后购物累计到返现中，直至返现总金额=消费总金额
** 第二种方式 jiecengfanxian ： 已jishu钱，为一个阶层， 只有达到第一个jishu, 则开始返现， 以后购物累计，看是否达到第2个jishu,第3，第4.。。
** 返现也是按照一个阶层来返， 返回第一个阶层， 若此时第二个阶层还没到到jishu， 则不返现， 当第二个阶层达到jishu， 开始返现第2个阶层。
*/
function check_user_is_fanxian($user_id){
	$fanxian = unserialize($GLOBALS['_CFG']['fanxian']);
	$jishu = $fanxian['fanxian_money_limit'];
	$type = $fanxian['fanxian_type'];
	
	$sql = "select is_disabled_point from ".$GLOBALS['ecs']->table('users')." where user_id = $user_id limit 1";
	$is_disabled_point = $GLOBALS['db']->getOne($sql);
	if($is_disabled_point == '1'){ //管理员禁用返现了
		return false;
	}
	printsqls($sql);
	$sql = "select consume,back_point from ".$GLOBALS['ecs']->table('user_backpoint')." where user_id = $user_id ";
	$data = $GLOBALS['db']->getRow($sql);
	$consume = floatval($data['consume']);
	$back_point = floatval($data['back_point']);
	printsqls($sql);
	if($type == 'jiecengfanxian'){
		$rest = floor($consume/$jishu) - floor($back_point/$jishu);
		if($rest>0){
			return true;
		}
	}else if($type == 'jinefanxian'){
		if($consume >= $jishu && $consume > $back_point){
			return true;
		}
	}
	return false;
}
function floatvalx($num, $xiaoshu){
	$xiaoshu = intval($xiaoshu)+1;
	return floatval(substr(number_format($num, $xiaoshu, '.', ''), 0, -1));
	
}

function printsqls($sql, $title=''){
	$flag = 0;
	if($flag){
		echo "<br>=============================<br>";
		echo $title."---[".date("Y-m-d H:i")."]<br>";
		echo $sql;
		echo "<br>=============================<br>";
	}
}

//365商城分佣金
//step1 : 查看用户总消费金额是否达到了分销级别，并进行设置
//step2: 对用户[当前订单]进行分佣行为
function expend_amount_order_log($order_id, $user_id){
    $fenxiao = unserialize($GLOBALS['_CFG']['fenxiao']);
    //查看消费总金额
	$sql = "select consume from ".$GLOBALS['ecs']->table('user_backpoint')." where user_id = '$user_id' ";
	$consume = $GLOBALS['db']->getOne($sql);
	$consume = floatval($consume);
	//echo "<br>#######  进入 expend_amount_order_log  ########<br>";
	if($consume>=365){
		$fenxiao_level = 365;
		if($consume >= 3650){
			$fenxiao_level = 3650;
		}
		if($fenxiao_level){
			set_user_to_fenxiao2($user_id);
			$sql = "update ".$GLOBALS['ecs']->table('user_expend')." set fenxiao_level = '$fenxiao_level', is_valid_user = 1,is_expend_valid = 1 , buyctime='".time()."' where user_id = '$user_id'";
			$GLOBALS['db']->query($sql);
		}
	}
	printsqls($sql,"设置分销等级");
	$order_amount = $GLOBALS['db']->getOne("select order_amount from ".$GLOBALS['ecs']->table('pay_log')." where order_id = ".$order_id);
	

	$level365 =  $fenxiao['dataitem']['zhitui_leve0'];
	$level3650 =  $fenxiao['dataitem']['zhitui_leve1'];

	$level365_le1 = $level365['sbs0_leve1'];
	$level365_le2 = $level365['sbs0_leve2'];
	$level365_le3 = $level365['sbs0_leve3'];
	$level365_money = $level365['sbs0_shop_amount'];
	
	
	$level3650_le1 = $level3650['sbs1_leve1'];
	$level3650_le2 = $level3650['sbs1_leve2'];
	$level3650_le3 = $level3650['sbs1_leve3'];
	$level3650_money = $level3650['sbs1_shop_amount'];

	if($order_amount>=$level365_money){
		$level_base = "level365";
		$fenxiao_level = 365;
		if($order_amount >= $level3650_money){
			$level_base = "level3650";
			$fenxiao_level = 3650;
		}
		if($fenxiao_level){
			$sql = "update ".$GLOBALS['ecs']->table('user_expend')." set fenxiao_level = '$fenxiao_level', is_valid_user = 1,is_expend_valid = 1 and buyctime='".time()."' where user_id = '$user_id'";
			$GLOBALS['db']->query($sql);
		}
		
		
		//第一级分销 
        $epuid_l1 = get_expend_puid($user_id);
        //var_dump($epuid_l1);
        if($epuid_l1){ //达到分销级别了，计算分佣
			$level1 = $level_base."_le1";
            $cur1 = floatval($$level1);
            save_ecs_user_expend_log2($user_id,$epuid_l1,0, $cur1,$order_amount,$order_id);
        }
        
        //第二级分销
        if($epuid_l1){
            $epuid_l2 = get_expend_puid($epuid_l1['uid']);
            if($epuid_l2){
				$level2 = $level_base."_le2";
                $cur2 = floatval($$level2);
                 save_ecs_user_expend_log2($user_id,$epuid_l2,0, $cur2,$order_amount,$order_id);
            }
        }else{
            $epuid_l2 = 0;
        }
        //第三级分销
        if($epuid_l2){
            $epuid_l3 = get_expend_puid($epuid_l2['uid']);
            if($epuid_l3){
				$level3 = $level_base."_le3";
                $cur3 = floatval($$level3);
                 save_ecs_user_expend_log2($user_id,$epuid_l3,0, $cur3,$order_amount,$order_id);
            }
        }else{
            $epuid_l3 = 0;
        }
		
		return 1;
	}else{
		return 0;
	}
    return 0;
}

//$order_amount ：在线付款金额，余额支付不包含在内
function save_ecs_user_expend_log2($userid,$expend_user_id,$level, $yongjin,$order_amount,$order_id){
    $expend_amount = $yongjin;
    $checksql = "select id from ".$GLOBALS['ecs']->table('user_expend_log')." where expend_user_id = $expend_user_id and order_id = ".$order_id." limit 1";
    $check = $GLOBALS['db']->getOne($checksql);
    //var_dump($check);
    if(!$check){ //检测是否此订单已给此用户分佣
        $sql = "insert into ".$GLOBALS['ecs']->table('user_expend_log')."(user_id, order_id,expend_level,expend_user_id,order_amount,expend_amount,status,ctime, expend_bili) values(".
                "'".$userid."',".
                "'".$order_id."',".
                "'".$level."',".
                "'".$expend_user_id."',".
                "'".$order_amount."',".
                "'".$expend_amount."',".
                "'0',".
                "'".time()."',".
                "'".$yongjin."'".
                ")";
        //echo "--->".$sql."<br>";
        $GLOBALS['db']->query($sql);
		common_push_expend_user2($expend_user_id, $order_id);
    }else{
        return true; //已分佣
    }
    
}


function common_push_expend_user2($uid, $oid){
	$id = intval($id);
	$sql = "select id, user_id, order_id, expend_user_id, order_amount, expend_amount, status ,ctime from ".$GLOBALS['ecs']->table('user_expend_log')." where order_id = $oid and expend_user_id = $uid ";
	//echo $sql."<br>";
	$info = $GLOBALS['db']->getRow($sql);
	if(!$info){ return 0;}
	$id = $info['id'];
	$userid = $info['expend_user_id'];
	$expend_amount = $info['expend_amount'];
	if($info['status'] == 1){ return 0;  }
	
	$sql = "update ".$GLOBALS['ecs']->table('user_expend_log')." set status = 1 where id = $id ";
	//echo $sql."<br>";
	$GLOBALS['db']->query($sql);
	
	//到账
	//累计到用户资金， 用户分佣金额
	set_fx_cash($userid,"+","expend_amount",$expend_amount);//累计用户分佣金额
			
	//佣金到账后，增加用户资金
	log_account_change($userid, $expend_amount, 0, 0, 0, '佣金到账，佣金id :'.$id, 3);
	return 1;
}


//确认订单后，设置为有效用户
function set_user_to_fenxiao2($userid){

	$sql =" select is_valid_user,expend_pid from ".$GLOBALS['ecs']->table('user_expend')." where user_id = $userid "; //设置当前用户为有效用户
	$is_fenxiao = $GLOBALS['db']->getRow($sql);
	if($is_fenxiao){
		$expend_pid = $is_fenxiao['expend_pid'];
		if($is_fenxiao['is_valid_user'] == '0'){
			$sql ="update ".$GLOBALS['ecs']->table('user_expend')." set is_valid_user = 1,is_expend_valid=1 where user_id = $userid ";
			$GLOBALS['db']->query($sql);
		}
	}else{
		$expend_pid = get_fenxiao_code();
		$expend_code = create_expend_code($uid,'');
		$sql = "insert into ".$GLOBALS['ecs']->table('user_expend')." (user_id, expend_pid, is_valid_user,is_expend_valid,expend_code) values($userid, $expend_pid, 1,1, '$expend_code')";
		$GLOBALS['db']->query($sql);
	}

	return 1;
}

//}}} 365
?>