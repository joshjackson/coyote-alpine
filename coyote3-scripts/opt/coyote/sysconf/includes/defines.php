<?php
    // This file should be included for any Coyote Linux scripts

    // Constant / global defines

	define("PRODUCT_WOLVERINE", 0x01000000);
	define("PRODUCT_VIPER", 0x02000000);
	define("PRODUCT_COYOTE", 0x03000000);
	define("PRODUCT_FURY", 0x04000000);
	define("PRODUCT_MASK", 0x7F000000);
	define("VERSION_MASK", 0x00FFFFFF);

	// Set the information about the current product
	define("PRODUCT_VERSION", "3.10");
	define("PRODUCT_ID", PRODUCT_COYOTE);
	define("PRODUCT_REG_VERSION", 0x30000);

    // Debugging mode bitmasks
    define("DEBUG_NONE", 0x00);
    define("DEBUG_PRINT", 0x01);
    define("DEBUG_NOEXEC", 0x02);

    debug("COYOTE_CONFIG_DIR", COYOTE_CONFIG_DIR."");
    debug("COYOTE_TEMPLATE_DIR", "/opt/coyote/sysconf/templates/")

?>