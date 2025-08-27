<?php
//This file is executed immediately after Zerolith and is optional to use.
//What can you do with it?
//- Send your app's login state here so that various zpanel utilities can work ( must be permission level 5 ).
//- Run your app's startup routine
//- Anything else!

//uncomment for authentication bypass for zerolith that makes zpanel utilities work on install 
//if(zl::$set['envChecks']) { zperm::setUser("0","zl_test","admin","5"); }