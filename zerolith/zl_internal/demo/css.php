<?php
require "../../zl_init.php";
zl::$page['navFunc'] = "";
zl::$page['wrap'] = true; // in case you had some settings disabling this.

zpage::start("zl.css cheatsheet and demo");
?>
<h2 class = "zl_marT-1">Padding</h2>
<div class = "zl_cols gap3">
	<div class = "col4">
		<b>zl_pad</b> ( square )<br><br>
		zl_pad0 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad0"><div class="zl_bgRed2">X</div></div><br>
		zl_pad1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad1"><div class="zl_bgRed2">X</div></div><br>
		zl_pad2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad2"><div class="zl_bgRed2">X</div></div><br>
		zl_pad3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad3"><div class="zl_bgRed2">X</div></div><br>
		zl_pad4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad4"><div class="zl_bgRed2">X</div></div><br>
		zl_pad5 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad5"><div class="zl_bgRed2">X</div></div><br>
		zl_pad6 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad6"><div class="zl_bgRed2">X</div></div><br>
	</div>
	<div class = "col4">
		<b>zl_padTB</b> ( top+bottom )<br><br>
		zl_padTB1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padTB1"><div class="zl_bgRed2">X</div></div><br>
		zl_padTB2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padTB2"><div class="zl_bgRed2">X</div></div><br>
		zl_padTB3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padTB3"><div class="zl_bgRed2">X</div></div><br>
		zl_padTB4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padTB4"><div class="zl_bgRed2">X</div></div><br>
		zl_padTB5 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padTB5"><div class="zl_bgRed2">X</div></div><br>
		zl_padTB6 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padTB6"><div class="zl_bgRed2">X</div></div><br>
	</div>
	<div class = "col4">
		<b>zl_padLR</b> ( left+right )<br><br>
		zl_padLR1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padLR1"><div class="zl_bgRed2">X</div></div><br>
		zl_padLR2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padLR2"><div class="zl_bgRed2">X</div></div><br>
		zl_padLR3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padLR3"><div class="zl_bgRed2">X</div></div><br>
		zl_padLR4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padLR4"><div class="zl_bgRed2">X</div></div><br>
		zl_padLR5 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padLR5"><div class="zl_bgRed2">X</div></div><br>
		zl_padLR6 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padLR6"><div class="zl_bgRed2">X</div></div><br>
	</div>
	<div class = "col4">
		<b>zl_padL/R/T/B</b><br>( any direction + space )<br><br>
		zl_padL1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padL1"><div class="zl_bgRed2">X</div></div><br>
		zl_padR2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padR2"><div class="zl_bgRed2">X</div></div><br>
		zl_padT3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padT3"><div class="zl_bgRed2">X</div></div><br>
		zl_padB4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padB4"><div class="zl_bgRed2">X</div></div><br>
	</div>
	<div class = "col4">
		<b>zl_padA</b> ( asymmetric )<br><br>
		zl_padA1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA1"><div class="zl_bgRed2">X</div></div><br>
		zl_padA2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA2"><div class="zl_bgRed2">X</div></div><br>
		zl_padA3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA3"><div class="zl_bgRed2">X</div></div><br>
		zl_padA4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA4"><div class="zl_bgRed2">X</div></div><br>
		zl_padA5 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA5"><div class="zl_bgRed2">X</div></div><br>
		zl_padA6 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA6"><div class="zl_bgRed2">X</div></div><br>
	</div>
	<div class = "col4">
		<b>zl_padA-</b><br>( neg. asymmetric )<br><br>
		zl_padA-1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA6"><div class="zl_padA-1"><div class="zl_bgRed2">X</div></div></div><br>
		zl_padA-2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA6"><div class="zl_padA-2"><div class="zl_bgRed2">X</div></div></div><br>
		zl_padA-3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA6"><div class="zl_padA-3"><div class="zl_bgRed2">X</div></div></div><br>
		zl_padA-4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA6"><div class="zl_padA-4"><div class="zl_bgRed2">X</div></div></div><br>
		zl_padA-5 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA6"><div class="zl_padA-5"><div class="zl_bgRed2">X</div></div></div><br>
		zl_padA-6 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_padA6"><div class="zl_padA-6"><div class="zl_bgRed2">X</div></div></div><br>
	</div>
</div>
<h2>Margin</h2>
<div class = "zl_cols gap3">
	<div class = "col4">
		<b>zl_mar</b> ( square )<br><br>
		zl_mar1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_mar1"><div class="zl_bgRed2">X</div></div><br>
		zl_mar2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_mar2"><div class="zl_bgRed2">X</div></div><br>
		zl_mar3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_mar3"><div class="zl_bgRed2">X</div></div><br>
		zl_mar4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_mar4"><div class="zl_bgRed2">X</div></div><br>
		zl_mar5 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_mar5"><div class="zl_bgRed2">X</div></div><br>
		zl_mar6 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_mar6"><div class="zl_bgRed2">X</div></div><br>
	</div>
	<div class = "col4">
		<b>zl_mar-</b> ( neg. square )<br><br>
		zl_mar-1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad6"><div class="zl_mar-1"><div class="zl_bgRed2">X</div></div></div><br>
		zl_mar-2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad6"><div class="zl_mar-2"><div class="zl_bgRed2">X</div></div></div><br>
		zl_mar-3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad6"><div class="zl_mar-3"><div class="zl_bgRed2">X</div></div></div><br>
		zl_mar-4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad6"><div class="zl_mar-4"><div class="zl_bgRed2">X</div></div></div><br>
		zl_mar-5 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad6"><div class="zl_mar-5"><div class="zl_bgRed2">X</div></div></div><br>
		zl_mar-6 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad6"><div class="zl_mar-6"><div class="zl_bgRed2">X</div></div></div><br>
	</div>
	<div class = "col4">
		<b>zl_marLR</b> ( left+right )<br><br>
		zl_marLR1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marLR1"><div class="zl_bgRed2">X</div></div><br>
		zl_marLR2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marLR2"><div class="zl_bgRed2">X</div></div><br>
		zl_marLR3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marLR3"><div class="zl_bgRed2">X</div></div><br>
		zl_marLR4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marLR4"><div class="zl_bgRed2">X</div></div><br>
		zl_marLR5 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marLR5"><div class="zl_bgRed2">X</div></div><br>
		zl_marLR6 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marLR6"><div class="zl_bgRed2">X</div></div><br>
	</div>
	<div class = "col4">
		<b>zl_marTB</b> ( top+bottom )<br><br>
		zl_marTB1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marTB1"><div class="zl_bgRed2">X</div></div><br>
		zl_marTB2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marTB2"><div class="zl_bgRed2">X</div></div><br>
		zl_marTB3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marTB3"><div class="zl_bgRed2">X</div></div><br>
		zl_marTB4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marTB4"><div class="zl_bgRed2">X</div></div><br>
		zl_marTB5 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marTB5"><div class="zl_bgRed2">X</div></div><br>
		zl_marTB6 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_marTB6"><div class="zl_bgRed2">X</div></div><br>
	</div>
	<div class = "col4">
		<b>zl_marL/R/T/B</b><br>( any direction + space value )<br><br>
		zl_marL1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib"><div class="zl_marL1"><div class="zl_bgRed2">X</div></div></div><br>
		zl_marR2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib"><div class="zl_marR2"><div class="zl_bgRed2">X</div></div></div><br>
		zl_marT3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib"><div class="zl_marT3"><div class="zl_bgRed2">X</div></div></div><br>
		zl_marB4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib"><div class="zl_marB4"><div class="zl_bgRed2">X</div></div></div><br>
	</div>
	<div class = "col4">
		<b>zl_marL/R/T/B/LR/TB-</b><br>( negative values exist for every class )<br><br>
		zl_marL-1 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad1"><div class="zl_marL-1"><div class="zl_bgRed2">X</div></div></div><br>
		zl_marR-2 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad2"><div class="zl_marR-2"><div class="zl_bgRed2">X</div></div></div><br>
		zl_marT-3 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad3"><div class="zl_marT-3"><div class="zl_bgRed2">X</div></div></div><br>
		zl_marB-4 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad4"><div class="zl_marB-4"><div class="zl_bgRed2">X</div></div></div><br>
		zl_marLR-5 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad6"><div class="zl_marLR-5"><div class="zl_bgRed2">X</div></div></div><br>
		zl_marTB-6 &nbsp;<div class = "zl_bordOkDark zl_marTB1 zl_bgGreen2 zl_ib zl_pad6"><div class="zl_marTB-6"><div class="zl_bgRed2">X</div></div></div><br>
	</div>
</div>

<h2>Modifiers</h2>

<div class = "zl_cols gap5">
	<div class = "col4">
		<b>Text</b><br><br>
		<table>
			<tr><td>zl_b</td><td><span class="zl_b">bold</span></td></tr>
			<tr><td>zl_small</td><td><span class="zl_small">Small text</span></td></tr>
			<tr><td>zl_pre</td><td><span class="zl_pre">Hi, i'm code</span></td></tr>
			<tr><td>zl_ellipsis</td><td><div class="zl_ellipsis zl_w88 zl_ib">Some long text</div></td></tr>
			<tr><td>zl_textL</td><td><span class="zl_textL zl_w100 zl_bordOkDark zl_ib">left align</span></td></tr>
			<tr><td>zl_textR</td><td><span class="zl_textR zl_w100 zl_bordOkDark zl_ib">right align</span></td></tr>
			<tr><td>zl_textC</td><td><span class="zl_textC zl_w100 zl_bordOkDark zl_ib">cent. align</span></td></tr>
			<tr><td>zl_textJ</td><td><span class="zl_textJ zl_w100 zl_bordOkDark zl_ib">just. align</span></td></tr>
			<tr><td>zl_shadTB1</td><td><span class="zl_shadTB1">Black 1</span></td></tr>
			<tr><td>zl_shadTB2</td><td><span class="zl_shadTB2">Black 2</span></td></tr>
			<tr><td>zl_shadTB3</td><td><span class="zl_shadTB3">Black 3</span></td></tr>
			<tr><td>zl_shadTW1</td><td class="zl_bgBW11 zl_bw1 zl_pad1"><span class="zl_shadTW1">White 1</span></td></tr>
			<tr><td>zl_shadTW2</td><td class="zl_bgBW11 zl_bw1 zl_pad1"><span class="zl_shadTW2">White 2</span></td></tr>
			<tr><td>zl_shadTW3</td><td class="zl_bgBW11 zl_bw1 zl_pad1"><span class="zl_shadTW3">White 3</span></td></tr>
		</table>
	</div>
	<div class = "col4">
		<b>Display Modifiers</b><br><br>
		<table>
			<tr><td>zl_scrollX</td><td><div class=" zl_ib zl_w75 zl_h50 zl_scrollX">Some Long Text</div></td></tr>
			<tr><td>zl_scrollY</td><td><div class=" zl_ib zl_w75 zl_h50 zl_scrollY">Some Long Text</div></td></tr>
			<tr><td>zl_scrollXAuto</td><td><div class=" zl_ib zl_w75 zl_h50 zl_scrollXAuto">Some Long Text Yep</div></td></tr>
			<tr><td>zl_scrollYAuto</td><td><div class=" zl_ib zl_w75 zl_h50 zl_scrollYAuto">Some Long Text Yep</div></td></tr>
			<tr><td>zl_wrap-</td><td><div class=" zl_ib zl_wrap-">Some Really Long Text</div></td></tr>
			<tr><td>zl_hovPoint</td><td><span class="zl_hovPoint">hover over me</span></td></tr>
			<tr><td>zl_stickyTx</td><td>( non-displayable )</td></tr>
		</table>
	</div>
	<div class = "col4">
		<b>Display Modes</b><br><br>
		<table>
			<tr><td>zl_ib,<br>zl_inline-block</td><td>X<div class="zl_ib zl_bgGreen2">thing</div></td></tr>
			<tr><td>zl_it,<br>zl_inline-table</td><td>X<div class="zl_it zl_bgGreen2">thing</div></td></tr>
			<tr><td>zl_hide</td><td><div class="zl_hide zl_bgGreen2">thing</div></td></tr>
			<tr><td>zl_block</td><td>X<div class="zl_block zl_bgGreen2">thing</div></td></tr>
			<tr><td>zl_flow-root</td><td>X<div class="zl_flow-root zl_bgGreen2">thing</div></td></tr>
			<tr><td>zl_l,<br>zl_left</td><td><div class="zl_w100p zl_bgGreen2"><span class="zl_left">thing</span></div></td></tr>
			<tr><td>zl_r,<br>zl_right</td><td><div class="zl_w100p zl_bgGreen2"><span class="zl_right">thing</span></div></td></tr>
		</table>
	</div>
	<div class = "col3 zl_bgBW11 zl_bw1">
		<b>White shadows</b><br><br>
		<table>
			<tr><td>zl_shad0</td><td><div class="zl_ib zl_shad- zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadW1</td><td><div class="zl_ib zl_shadW1 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadW2</td><td><div class="zl_ib zl_shadW2 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadW3</td><td><div class="zl_ib zl_shadW3 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadW4</td><td><div class="zl_ib zl_shadW4 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadW5</td><td><div class="zl_ib zl_shadW5 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadW6</td><td><div class="zl_ib zl_shadW6 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
		</table>
	</div>
	<div class = "col3 zl_bgBW1">
		<b>Black shadows</b><br><br>
		<table>
			<tr><td>zl_shad0</td><td><div class="zl_ib zl_shad- zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadB1</td><td><div class="zl_ib zl_shadB1 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadB2</td><td><div class="zl_ib zl_shadB2 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadB3</td><td><div class="zl_ib zl_shadB3 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadB4</td><td><div class="zl_ib zl_shadB4 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadB5</td><td><div class="zl_ib zl_shadB5 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_shadB6</td><td><div class="zl_ib zl_shadB6 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
		</table>
	</div>
	<div class = "col2">
		<b>Radiuses</b><br><br>
		<table>
			<tr><td>zl_rad0</td><td><div class="zl_ib zl_rad0 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_rad1</td><td><div class="zl_ib zl_rad1 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_rad2</td><td><div class="zl_ib zl_rad2 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_rad3</td><td><div class="zl_ib zl_rad3 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_rad4</td><td><div class="zl_ib zl_rad4 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_rad5</td><td><div class="zl_ib zl_rad5 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
			<tr><td>zl_rad6</td><td><div class="zl_ib zl_rad6 zl_w20 zl_bgRed5">&nbsp;</div></td></tr>
		</table>
	</div>
</div>

<h2>Colors & opacity</h2>

<div class="zl_cols">
	<div class = "col">
		<b>Opacities</b><br><br>
		<table>
			<tr><td>zl_opa0</td><td><div class="zl_ib zl_opa0 zl_w20 zl_bgRed11">&nbsp;</div></td></tr>
			<tr><td>zl_opa1</td><td><div class="zl_ib zl_opa1 zl_w20 zl_bgRed11">&nbsp;</div></td></tr>
			<tr><td>zl_opa2</td><td><div class="zl_ib zl_opa2 zl_w20 zl_bgRed11">&nbsp;</div></td></tr>
			<tr><td>zl_opa3</td><td><div class="zl_ib zl_opa3 zl_w20 zl_bgRed11">&nbsp;</div></td></tr>
			<tr><td>zl_opa4</td><td><div class="zl_ib zl_opa4 zl_w20 zl_bgRed11">&nbsp;</div></td></tr>
			<tr><td>zl_opa5</td><td><div class="zl_ib zl_opa5 zl_w20 zl_bgRed11">&nbsp;</div></td></tr>
			<tr><td>zl_opa6</td><td><div class="zl_ib zl_opa6 zl_w20 zl_bgRed11">&nbsp;</div></td></tr>
			<tr><td>zl_opa7</td><td><div class="zl_ib zl_opa7 zl_w20 zl_bgRed11">&nbsp;</div></td></tr>
			<tr><td>zl_opa8</td><td><div class="zl_ib zl_opa8 zl_w20 zl_bgRed11">&nbsp;</div></td></tr>
			<tr><td>zl_opa9</td><td><div class="zl_ib zl_opa9 zl_w20 zl_bgRed11">&nbsp;</div></td></tr>
			<tr><td>zl_opa10</td><td><div class="zl_ib zl_opa10 zl_w20 zl_bgRed11">&nbsp;</div></td></tr>
		</table>
	</div>
	<div class = "col2">
		<b>Full palette shortcuts</b><br>
		Variant: zl_bg(color) for bg color<br><br>
		<table>
			<tr><td>zl_white</td><td><div class="zl_ib zl_w20 zl_bgWhite">&nbsp;</div></td></tr>
			<tr><td>zl_whiteDark</td><td><div class="zl_ib zl_w20 zl_bgWhiteDark">&nbsp;</div></td></tr>
			<tr><td>zl_whiteDarkDark</td><td><div class="zl_ib zl_w20 zl_bgWhiteDarkDark">&nbsp;</div></td></tr>
			<tr><td>zl_grey</td><td><div class="zl_ib zl_w20 zl_bgGrey">&nbsp;</div></td></tr>
			<tr><td>zl_greyDark</td><td><div class="zl_ib zl_w20 zl_bgGreyDark">&nbsp;</div></td></tr>
			<tr><td>zl_black</td><td><div class="zl_ib zl_w20 zl_bgBlack">&nbsp;</div></td></tr>
			<tr><td>zl_blackDark</td><td><div class="zl_ib zl_w20 zl_bgBlackDark">&nbsp;</div></td></tr>
			<tr><td>zl_red</td><td><div class="zl_ib zl_w20 zl_bgRed">&nbsp;</div></td></tr>
			<tr><td>zl_pink</td><td><div class="zl_ib zl_w20 zl_bgPink">&nbsp;</div></td></tr>
			<tr><td>zl_purple</td><td><div class="zl_ib zl_w20 zl_bgPurple">&nbsp;</div></td></tr>
			<tr><td>zl_purpleDeep</td><td><div class="zl_ib zl_w20 zl_bgPurpleDeep">&nbsp;</div></td></tr>
			<tr><td>zl_indigo</td><td><div class="zl_ib zl_w20 zl_bgIndigo">&nbsp;</div></td></tr>
			<tr><td>zl_blue</td><td><div class="zl_ib zl_w20 zl_bgBlue">&nbsp;</div></td></tr>
			<tr><td>zl_blueLight</td><td><div class="zl_ib zl_w20 zl_bgBlueLight">&nbsp;</div></td></tr>
			<tr><td>zl_cyan</td><td><div class="zl_ib zl_w20 zl_bgCyan">&nbsp;</div></td></tr>
			<tr><td>zl_teal</td><td><div class="zl_ib zl_w20 zl_bgTeal">&nbsp;</div></td></tr>
			<tr><td>zl_green</td><td><div class="zl_ib zl_w20 zl_bgGreen">&nbsp;</div></td></tr>
			<tr><td>zl_greenLight</td><td><div class="zl_ib zl_w20 zl_bgGreenLight">&nbsp;</div></td></tr>
			<tr><td>zl_lime</td><td><div class="zl_ib zl_w20 zl_bgLime">&nbsp;</div></td></tr>
			<tr><td>zl_yellow</td><td><div class="zl_ib zl_w20 zl_bgYellow">&nbsp;</div></td></tr>
			<tr><td>zl_amber</td><td><div class="zl_ib zl_w20 zl_bgAmber">&nbsp;</div></td></tr>
			<tr><td>zl_orange</td><td><div class="zl_ib zl_w20 zl_bgOrange">&nbsp;</div></td></tr>
			<tr><td>zl_deepOrange</td><td><div class="zl_ib zl_w20 zl_bgDeepOrange">&nbsp;</div></td></tr>
			<tr><td>zl_brown</td><td><div class="zl_ib zl_w20 zl_bgBrown">&nbsp;</div></td></tr>
			<tr><td>zl_blueGrey</td><td><div class="zl_ib zl_w20 zl_bgBlueGrey">&nbsp;</div></td></tr>
		</table>
	</div>
	<div class = "col3">
		<b>Colors ZL</b><br>
		Variant: zl_bg(color) for bg colors<br><br>
		<table>
			<tr><td>zl_BW1</td><td><div class="zl_ib zl_bgBW1 zl_w20">&nbsp;</div></td>     <td>zl_accentLight</td><td><div class="zl_ib zl_bgAccentLight zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_BW2</td><td><div class="zl_ib zl_bgBW2 zl_w20">&nbsp;</div></td>     <td>zl_accent</td><td><div class="zl_ib zl_bgAccent zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_BW3</td><td><div class="zl_ib zl_bgBW3 zl_w20">&nbsp;</div></td>     <td>zl_accentDark</td><td><div class="zl_ib zl_bgAccentDark zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_BW4</td><td><div class="zl_ib zl_bgBW4 zl_w20">&nbsp;</div></td>     <td>zl_accent2Light</td><td><div class="zl_ib zl_bgAccent2Light zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_BW5</td><td><div class="zl_ib zl_bgBW5 zl_w20">&nbsp;</div></td>     <td>zl_accent2</td><td><div class="zl_ib zl_bgAccent2 zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_BW6</td><td><div class="zl_ib zl_bgBW6 zl_w20">&nbsp;</div></td>     <td>zl_accent2Dark</td><td><div class="zl_ib zl_bgAccent2Dark zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_BW7</td><td><div class="zl_ib zl_bgBW7 zl_w20">&nbsp;</div></td>     <td>zl_accent3Light</td><td><div class="zl_ib zl_bgAccent3Light zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_BW8</td><td><div class="zl_ib zl_bgBW8 zl_w20">&nbsp;</div></td>     <td>zl_accent3</td><td><div class="zl_ib zl_bgAccent3 zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_BW9</td><td><div class="zl_ib zl_bgBW9 zl_w20">&nbsp;</div></td>     <td>zl_accent3Dark</td><td><div class="zl_ib zl_bgAccent3Dark zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_BW10</td><td><div class="zl_ib zl_bgBW10 zl_w20">&nbsp;</div></td>   <td>zl_warnLight</td><td><div class="zl_ib zl_bgWarnLight zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_BW11</td><td><div class="zl_ib zl_bgBW11 zl_w20">&nbsp;</div></td>   <td>zl_warn</td><td><div class="zl_ib zl_bgWarn zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_TH</td><td><div class="zl_ib zl_bgTH zl_w20">&nbsp;</div></td>       <td>zl_warnDark</td><td><div class="zl_ib zl_bgWarnDark zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_THDark</td><td><div class="zl_ib zl_bgTHDark zl_w20">&nbsp;</div></td>       <td>zl_errLight</td><td><div class="zl_ib zl_bgErrLight zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_THText</td><td><div class="zl_ib zl_bgTHText zl_w20">&nbsp;</div></td>       <td>zl_err</td><td><div class="zl_ib zl_bgErr zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_Link</td><td><div class="zl_ib zl_bgLink zl_w20">&nbsp;</div></td>           <td>zl_errDark</td><td><div class="zl_ib zl_bgErrDark zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_LinkLight</td><td><div class="zl_ib zl_bgLinkLight zl_w20">&nbsp;</div></td> <td>zl_okLight</td><td><div class="zl_ib zl_bgOkLight zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_LinkDark</td><td><div class="zl_ib zl_bgLinkDark zl_w20">&nbsp;</div></td>   <td>zl_ok</td><td><div class="zl_ib zl_bgOk zl_w20">&nbsp;</div></td></tr>
			<tr><td>zl_LinkDarkDark</td><td><div class="zl_ib zl_bgLinkDarkDark zl_w20">&nbsp;</div></td><td>zl_okDark</td><td><div class="zl_ib zl_bgOkDark zl_w20">&nbsp;</div></td></tr>
		</table>
	</div>
	<div class = "col2">
		<b>Borders ZL</b><br>
		Variants: zl_bord(L/R/T/B)(color name)<br><br>
		<table>
			<tr><td>zl_bord0</td><td><div class="zl_ib zl_bord0 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordAccentLight</td><td><div class="zl_ib zl_bordAccentLight zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordBW1</td><td><div class="zl_ib zl_bordBW1 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordAccent</td><td><div class="zl_ib zl_bordAccent zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordBW2</td><td><div class="zl_ib zl_bordBW2 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordAccentDark</td><td><div class="zl_ib zl_bordAccentDark zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordBW3</td><td><div class="zl_ib zl_bordBW3 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordAccent2Light</td><td><div class="zl_ib zl_bordAccent2Light zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordBW4</td><td><div class="zl_ib zl_bordBW4 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordAccent2</td><td><div class="zl_ib zl_bordAccent2 zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordBW5</td><td><div class="zl_ib zl_bordBW5 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordAccent2Dark</td><td><div class="zl_ib zl_bordAccent2Dark zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordBW6</td><td><div class="zl_ib zl_bordBW6 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordAccent3Light</td><td><div class="zl_ib zl_bordAccent3Light zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordBW7</td><td><div class="zl_ib zl_bordBW7 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordAccent3</td><td><div class="zl_ib zl_bord3Accent zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordBW8</td><td><div class="zl_ib zl_bordBW8 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordAccent3Dark</td><td><div class="zl_ib zl_bordAccent3Dark zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordBW9</td><td><div class="zl_ib zl_bordBW9 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordWarnLight</td><td><div class="zl_ib zl_bordWarnLight zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordBW10</td><td><div class="zl_ib zl_bordBW10 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordWarn</td><td><div class="zl_ib zl_bordWarn zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordBW11</td><td><div class="zl_ib zl_bordBW11 zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordWarnDark</td><td><div class="zl_ib zl_bordWarnDark zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordTH</td><td><div class="zl_ib zl_bordTH zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordErrLight</td><td><div class="zl_ib zl_bordErrLight zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordTHDark</td><td><div class="zl_ib zl_bordTHDark zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordErr</td><td><div class="zl_ib zl_bordErr zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordTHText</td><td><div class="zl_ib zl_bordTHText zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordErrDark</td><td><div class="zl_ib zl_bordErrDark zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordLink</td><td><div class="zl_ib zl_bordLink zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordOkLight</td><td><div class="zl_ib zl_bordOkLight zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordLinkLight</td><td><div class="zl_ib zl_bordLinkLight zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordOk</td><td><div class="zl_ib zl_bordOk zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordLinkDark</td><td><div class="zl_ib zl_bordLinkDark zl_w20 zl_bgBW1">&nbsp;</div></td><td>zl_bordOkDark</td><td><div class="zl_ib zl_bordOkDark zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
			<tr><td>zl_bordLinkDarkDark</td><td><div class="zl_ib zl_bordLinkDarkDark zl_w20 zl_bgBW1">&nbsp;</div></td></tr>
		</table>
	</div>
</div>
<br>
<div class="zl_cols">
	<div class = "col4">
		<b>Full Palette</b><br><br>
		<?php
		$colorTags = ['red','pink','purple','purpleDeep','indigo','blue','blueLight','cyan','teal','green','greenLight','lime','yellow','amber','orange','deepOrange','brown', 'blueGrey', 'BW'];
		
		?><div class = "zl_ib zl_small zl_b zl_white"><?php
		for($i = 1; $i < 12; $i++)
		{
			foreach($colorTags as $colorTag)
			{ ?><div class = "zl_pad1 zl_mar1 zl_ib zl_w20 zl_h20 zl_bg<?=ucfirst($colorTag)?><?=$i?>"><span class="zl_opa5"><?=$i?></span></div><?php }
			
			echo "<br>";
		}
		foreach($colorTags as $colorTag) { ?><div class = "zl_pad1 zl_mar1 zl_ib zl_w20 zl_bg slant">zl_<?=$colorTag?></div><?php }
		?></div>
	</div>
	<div class = "col3">
		<b>Full Palette Vivid</b><br><br>
		<?php
		$colorTags = ['red','pink','purple','purpleDeep','indigo','blue','blueLight','cyan','teal','green','greenLight','lime','yellow','amber','orange','deepOrange'];
		
		?><div class = "zl_ib zl_small zl_b zl_white"><?php
		for($i = 1; $i < 5; $i++)
		{
			foreach($colorTags as $colorTag)
			{ ?><div class = "zl_pad1 zl_mar1 zl_ib zl_w20 zl_h20 zl_bg<?=ucfirst($colorTag)?>Viv<?=$i?>"><span class="zl_opa5"><?=$i?></span></div><?php }
			
			echo "<br>";
		}
		foreach($colorTags as $colorTag) { ?><div class = "zl_pad1 zl_mar1 zl_ib zl_w20 zl_bg slant">zl_<?=$colorTag?></div><?php }
		?></div>
	</div>
</div>

<div class="zl_cols">
	<div class = "col4">
		<b>ZL Extended Color set</b><br><br>
		<?php
		$colorTags = ['red','pink','purple','purpleDeep','indigo','blue','blueLight','cyan','teal','green','greenLight','lime','yellow','amber','orange','deepOrange','brown', 'blueGrey', 'BW'];
		
		?><div class = "zl_ib zl_small zl_b zl_white"><?php
		for($i = 1; $i < 12; $i++)
		{
			foreach($colorTags as $colorTag)
			{ ?><div class = "zl_pad1 zl_mar1 zl_ib zl_w20 zl_h20 zl_bg<?=ucfirst($colorTag)?><?=$i?>"><span class="zl_opa5"><?=$i?></span></div><?php }
			
			echo "<br>";
		}
		foreach($colorTags as $colorTag) { ?><div class = "zl_pad1 zl_mar1 zl_ib zl_w20 zl_bg slant">zl_<?=$colorTag?></div><?php }
		?></div>
	</div>
</div>

<h2>Flex columns & mobile</h2>

<h2>Height/Width</h2>



<style>
	table td { padding: 5px; vertical-align:top; }
	.slant { transform:rotate(40deg); color:black; }
</style>