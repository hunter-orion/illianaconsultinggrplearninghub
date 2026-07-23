import "../css/courses.scss";
import { createRoot } from "react-dom/client";

// Exposed on window because the tab markup (currently static in
// single-sfwd-courses.php, eventually output by the course-panels block's
// render_callback) uses inline onclick="showTab(...)" attributes — those
// look up the function on the global scope, which webpack's module wrapper
// doesn't expose by default.


function FrontendTest() {
	return (
		<div>from frontend</div>
	);
}

const mountEl = document.getElementById( 'courses-frontend-app' );
if ( mountEl ) {
	createRoot( mountEl ).render( <FrontendTest /> );
}
