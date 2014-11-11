<?php
/**
 * File containg permission defination according to feature of site.
 * All permision check should access this class
 * 
 * @author Santosh Singh <santosh@incaendo.com>
 * This file is part of <Civico>.
 * This file can not be copied and/or distributed without the express permission 
 * of <ahref Foundation.
 */

/**
 * This Class will have all static variables.
 * $features variable is used to define all features of website where composite 
 *   permission check is to be applied.
 * All features defined in $features varaible must be explictly define as a 
 *   sepreate variable and it should define the permission valid for that 
 *   feature.
 * 
 * checkPermission function will automatically ready updates from this file.
 * 
 */

class featurePermission {
    
    public static $features = array('admin', 'role');
    
    public static $admin = array('is admin');
    
    public static $role = array('Can mark highlighted', 'Can post answers on opinion');
    
}


?>
