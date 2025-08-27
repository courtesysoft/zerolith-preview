<?php
//monkeypatch existing zerolith settings just for zpanel
zl::setOutFormat("page");
zl::$page['wrap'] = true;
zpage::$pageEndHTML = 
'<style>
#zl_sideNav{ width:155px; } .zlt_navItem { font-size: 14.5px; } 
@media only screen and (min-width: 1000px) 
{
    #zlt_mainWrap { padding: 20px 20px 20px calc(155px + 20px) !important; }
    .zlt_navItem .zlt_micon { font-size: 18px !important; }
}
#zlt_navHead b { font-size:14px; }
</style>
'; //force nav to a specific width
//zl::$page['navWidth'] = 110;
zl::$page['logoLink'] = "/zerolith/zpanel/index.php";
zl::setDebugLevel(4); //use safe debug level in case 
?>