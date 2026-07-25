import "../css/courses.scss";
import { createRoot } from "react-dom/client";
import { useState } from "react";

// Exposed on window because the tab markup (rendered by the course-panels
// block's render_callback) uses inline onclick="showTab(...)" attributes —
// those look up the function on the global scope, which webpack's module
// wrapper doesn't expose by default.
window.showTab = function ( id, el ) {
	document.querySelectorAll( '.tab-content' ).forEach( ( t ) => t.classList.remove( 'active' ) );
	document.querySelectorAll( '.tab' ).forEach( ( t ) => t.classList.remove( 'active' ) );
	document.getElementById( id ).classList.add( 'active' );
	el.classList.add( 'active' );
};
function CourseAnnouncements( props ) {
	const items = props.announcements || [];
	if ( ! items.length ) {
		return null;
	}

	return (
		<>
			{ items.map( ( a, i ) => (
				<div className={ 'announce-card' + ( a.pinned ? ' pinned' : '' ) } key={ i }>
					{ a.pinned && <div className="pin-label">📌 Pinned</div> }
					<div className="announce-head">
						<div className="announce-who">
							<div className="name">{ a.name }</div>
							{ a.tag && <span className="tag">{ a.tag }</span> }
						</div>
						<div className="announce-time">{ a.time }</div>
					</div>
					<div className="announce-body">{ a.body }</div>
				</div>
			) ) }
		</>
	);
}

function CourseSyllabus( props ) {
	const requires = props.completeRequires || [];

	return (
		<>
			<div className="syl-section">
				<h3>Meet Your Instructor</h3>
				<div className="instructor-card">
					<div className="avatar">{ props.instructorInitials }</div>
					<div className="instructor-card-text">
						<h4>{ props.instructorName }</h4>
						<div className="cred">{ props.instructorCred }</div>
						<p>{ props.instructorBio }</p>
					</div>
				</div>
			</div>

			<div className="syl-section">
				<h3>How This Course Works</h3>
				<p>{ props.howItWorks }</p>
			</div>

			{ !! requires.length && (
				<div className="syl-section">
					<h3>What "Complete" Requires</h3>
					<ul className="req-list">
						{ requires.map( ( item, i ) => (
							<li key={ i }>
								<div className="dot"></div>
								<div>{ item }</div>
							</li>
						) ) }
					</ul>
				</div>
			) }

			<div className="syl-section">
				<h3>Time Commitment</h3>
				<p>{ props.timeCommitment }</p>
			</div>

			<div className="syl-section">
				<h3>Need Help?</h3>
				<p>{ props.needHelp }</p>
			</div>
		</>
	);
}

// "Illiana consulting" events are always scheduled in Eastern time, and the
// datetime-local inputs in the block editor store naive local values (no
// timezone) — so ctz is hardcoded here to match rather than trusting the
// visitor's own timezone, which google.calendar/render would otherwise
// silently assume.
const EVENT_TIMEZONE = 'America/New_York';

// datetime-local gives "YYYY-MM-DDTHH:mm" — Google's dates= param wants that
// same naive local value (paired with ctz above) as "YYYYMMDDTHHmmss".
function toGCalDateTime( localDateTime ) {
	if ( ! localDateTime ) {
		return '';
	}
	return localDateTime.replace( /[-:]/g, '' ) + '00';
}

function buildGoogleCalendarUrl( { title, start, end, location } ) {
	const params = new URLSearchParams( {
		action: 'TEMPLATE',
		text: title || '',
		dates: `${ toGCalDateTime( start ) }/${ toGCalDateTime( end || start ) }`,
		location: location || '',
		ctz: EVENT_TIMEZONE,
	} );
	return `https://calendar.google.com/calendar/render?${ params.toString() }`;
}

function CourseDates( props ) {
	const dates = props.dates || [];
	if ( ! dates.length ) {
		return null;
	}

	return (
		<>
			{ dates.map( ( d, i ) => {
				const startDate = d.start ? new Date( d.start ) : null;
				const endDate = d.end ? new Date( d.end ) : null;
				const month = startDate ? startDate.toLocaleString( 'en-US', { month: 'short' } ).toUpperCase() : '';
				const day = startDate ? startDate.getDate() : '';
				const timeLabel = startDate
					? startDate.toLocaleTimeString( 'en-US', { hour: 'numeric', minute: '2-digit' } )
						+ ( endDate ? ` – ${ endDate.toLocaleTimeString( 'en-US', { hour: 'numeric', minute: '2-digit' } ) }` : '' )
					: '';
				const sub = [ timeLabel, d.location ].filter( Boolean ).join( ' · ' );

				return (
					<div className="date-row" key={ i }>
						<div className="date-box">
							<div className="m">{ month }</div>
							<div className="d">{ day }</div>
						</div>
						<div className="date-info">
							<div className="title">{ d.title }</div>
							<div className="sub">{ sub }</div>
						</div>
						<a className="cal-btn" href={ buildGoogleCalendarUrl( d ) } target="_blank" rel="noopener noreferrer">
							+ Calendar
						</a>
					</div>
				);
			} ) }
		</>
	);
}

function CourseTemplates( props ) {
	const templates = props.templates || [];

	if ( ! templates.length ) {
		return null;
	}

	return (
		<div className="tmpl-grid">
			{ templates.map( ( t, i ) => (
				<div className="tmpl-card" key={ i }>
					<div className="tmpl-icon">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
							<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
							<path d="M14 2v6h6" />
						</svg>
					</div>
					<div className="tmpl-info">
						<div className="name">{ t.name }</div>
						<div className="filetype">{ ( t.fileType || '' ).toUpperCase() }</div>
					</div>
					<a className="tmpl-dl" href={ t.fileUrl } download>
						Download
					</a>
				</div>
			) ) }
		</div>
	);
}

function CourseDiscussion( props ) {
	const [ threads, setThreads ] = useState( props.threads || [] );
	const [ expandedId, setExpandedId ] = useState( null );
	const [ replyText, setReplyText ] = useState( '' );
	const [ posting, setPosting ] = useState( false );
	const [ error, setError ] = useState( '' );

	if ( ! threads.length ) {
		return null;
	}

	const participatedCount = threads.filter( ( t ) => t.participated ).length;
	const participationPct = Math.round( ( participatedCount / threads.length ) * 100 );

	const toggleThread = ( lessonId ) => {
		setError( '' );
		setReplyText( '' );
		setExpandedId( expandedId === lessonId ? null : lessonId );
	};

	const postReply = ( lessonId ) => {
		const content = replyText.trim();
		if ( ! content || posting ) {
			return;
		}

		setPosting( true );
		setError( '' );
		console.log(window.illianaCoursesData.restUrl)

		fetch( window.illianaCoursesData.restUrl + 'comments', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': window.illianaCoursesData.nonce,
			},
			body: JSON.stringify( { post: lessonId, content } ),
		} )
			.then( ( res ) => {
				if ( ! res.ok ) {
					throw new Error( 'Failed to post reply.' );
				}
				return res.json();
			} )
			.then( ( comment ) => {
				setThreads( ( prev ) =>
					prev.map( ( t ) =>
						t.lessonId !== lessonId
							? t
							: {
									...t,
									participated: true,
									commentCount: t.commentCount + 1,
									comments: [
										...t.comments,
										{
											id: comment.id,
											// Not comment.author_name — that's just WP_Comment's
											// stored comment_author (display_name at post time),
											// which defaults to the login email on this site.
											author: window.illianaCoursesData.userName || comment.author_name,
											content: comment.content.rendered,
											time: 'just now',
											isMine: true,
										},
									],
							  }
					)
				);
				setReplyText( '' );
			} )
			.catch( () => setError( 'Something went wrong posting your reply — try again.' ) )
			.finally( () => setPosting( false ) );
	};

	return (
		<>
			<div className="participation-tracker">
				<div>
					<div className="label">Your Discussion Participation</div>
					<div className="value">
						{ participatedCount } of { threads.length } lessons
					</div>
				</div>
				<div className="part-track">
					<div className="part-fill" style={ { width: participationPct + '%' } }></div>
				</div>
				<div className="note">Post at least once per lesson to track your participation.</div>
			</div>

			{ threads.map( ( t ) => (
				<div key={ t.lessonId }>
					<div className="thread-row" onClick={ () => toggleThread( t.lessonId ) }>
						<div className="t-left">
							<div className="t-title">
								{ t.participated ? '✓ ' : '' }
								{ t.title }
							</div>
							<div className="t-phase">{ t.commentCount } replies</div>
						</div>
						<div className="t-meta">{ t.participated ? 'You participated' : '' }</div>
					</div>

					{ expandedId === t.lessonId && (
						<div className="thread-expanded">
							<h4>{ t.title }</h4>

							{ ! t.comments.length && <div className="prompt">No replies yet — be the first.</div> }

							{ t.comments.map( ( c ) => (
								<div className={ 'post' + ( c.isMine ? ' instructor' : '' ) } key={ c.id }>
									<div className="avatar">{ ( c.author || '?' ).slice( 0, 2 ).toUpperCase() }</div>
									<div className="post-body">
										<div className="post-head">
											<span className="name">{ c.author }</span>
											<span className="time">{ c.time }</span>
										</div>
										<div
											className="post-text"
											dangerouslySetInnerHTML={ { __html: c.content } }
										/>
									</div>
								</div>
							) ) }

							{ props.isLoggedIn ? (
								<div className="reply-box">
									<textarea
										placeholder="Add your reply..."
										value={ replyText }
										onChange={ ( e ) => setReplyText( e.target.value ) }
									/>
									<button
										className="reply-btn"
										disabled={ posting }
										onClick={ () => postReply( t.lessonId ) }
									>
										{ posting ? 'Posting...' : 'Post' }
									</button>
								</div>
							) : (
								<p className="prompt">Log in to join the discussion.</p>
							) }

							{ error && <p className="prompt">{ error }</p> }
						</div>
					) }
				</div>
			) ) }
		</>
	);
}

// Blocks render a PHP shell — a hidden JSON blob of their attributes inside
// a `.courses-block-mount` element — via render_callback. This finds those
// mounts and hydrates the real visual output client-side.
const blockComponents = {
	'course-announcements': CourseAnnouncements,
	'course-syllabus': CourseSyllabus,
	'course-dates': CourseDates,
	'course-discussion': CourseDiscussion,
	'course-templates': CourseTemplates,
};

document.querySelectorAll( '.courses-block-mount' ).forEach( ( mountEl ) => {
	const blockName = mountEl.dataset.coursesBlock;
	const Component = blockComponents[ blockName ];
	if ( ! Component ) {
		return;
	}

	const dataEl = mountEl.querySelector( 'pre' );
	const data = dataEl ? JSON.parse( dataEl.textContent ) : {};

	createRoot( mountEl ).render( <Component { ...data } /> );
} );
