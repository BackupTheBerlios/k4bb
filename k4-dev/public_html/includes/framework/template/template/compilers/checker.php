<?php
/**********************************************************************************
 *     File Arts
 *     Copyright (c) 2005, Peter Goodman, BestWebEver.com
 *********************************************************************************/

class Isadmin_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		return "<?php if (\$_SESSION['user'] instanceof Member && \$_SESSION['user']['perms'] >= ADMIN): ?>";
	}
	function getClose(&$element) {
		return "<?php endif; ?>";
	}
}

class Unlogged_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		return "<?php if (!(\$_SESSION['user'] instanceof Member)): ?>";
	}
	function getClose(&$element) {
		return "<?php endif; ?>";
	}
}

class Logged_Compiler extends TPL_Tag_Compiler {
	function getOpen(&$element) {
		return "<?php if (\$_SESSION['user'] instanceof Member): ?>";
	}
	function getClose(&$element) {
		return "<?php endif; ?>";
	}
}

?>