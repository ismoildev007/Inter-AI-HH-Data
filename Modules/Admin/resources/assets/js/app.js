// Duralux Admin - minimal login required scripts
import "../vendors/js/vendors.min.js";
import "../vendors/js/nxlNavigation.min.js";
import "../js/common-init.min.js";

// Ensure these assets are included in the manifest for Blade asset() usage
import "../images/logo-abbr.png";
import "../images/favicon.ico";
import "../images/logo-full.png";
import "../images/avatar/1.png";
import "../vendors/img/flags/4x3/us.svg";
import "../vendors/img/flags/1x1/us.svg";
import "../vendors/img/flags/1x1/sa.svg";
import "../vendors/img/flags/1x1/bd.svg";
import "../vendors/img/flags/1x1/ch.svg";
import "../vendors/img/flags/1x1/nl.svg";

// Dashboard/Analytics initializers
if (document.querySelector('#payment-records-chart') || document.querySelector('#visitors-overview-statistics-chart')) {
  import('../js/dashboard-init.min.js');
  import('../js/analytics-init.min.js');
}
