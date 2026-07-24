import { registerBlockType } from '@wordpress/blocks';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, TextControl, TextareaControl, CheckboxControl } from '@wordpress/components';

const sectionStyle = {
	marginBottom: '24px',
	paddingBottom: '20px',
	borderBottom: '1px solid #ddd',
};

const rowStyle = {
	display: 'flex',
	alignItems: 'flex-start',
	gap: '10px',
	marginBottom: '10px',
	padding: '10px',
	border: '1px solid #ddd',
	borderRadius: '4px',
	flexWrap: 'wrap',
};

// Shared add/update/remove behavior for the four repeatable lists
// (announcements, key dates, templates, and the plain-string "complete
// requires" bullets). `items`/`onChange` are the attribute array + setter;
// `renderRow` gets (row, index, updateRow) and returns the row's fields.
function Repeater( { label, items, onChange, renderRow, newRow } ) {
	const updateRow = ( index, changes ) => {
		const next = items.slice();
		next[ index ] = typeof changes === 'object' && changes !== null && ! Array.isArray( changes )
			? { ...next[ index ], ...changes }
			: changes;
		onChange( next );
	};

	const removeRow = ( index ) => onChange( items.filter( ( _, i ) => i !== index ) );
	const addRow = () => onChange( [ ...items, newRow ] );

	return (
		<div style={ sectionStyle }>
			<h3>{ label }</h3>
			{ items.map( ( row, index ) => (
				<div key={ index } style={ rowStyle }>
					{ renderRow( row, index, updateRow ) }
					<Button isDestructive variant="tertiary" onClick={ () => removeRow( index ) }>
						Remove
					</Button>
				</div>
			) ) }
			<Button variant="primary" onClick={ addRow }>
				+ Add
			</Button>
		</div>
	);
}

registerBlockType( 'illiana/course-panels', {
	title: 'Course Panels',
	icon: 'layout',
	category: 'widgets',
	attributes: {
		announcements: { type: 'array', default: [] },
		howItWorks: { type: 'string', default: '' },
		instructorName: { type: 'string', default: '' },
		instructorInitials: { type: 'string', default: '' },
		instructorCred: { type: 'string', default: '' },
		instructorBio: { type: 'string', default: '' },
		completeRequires: { type: 'array', default: [] },
		timeCommitment: { type: 'string', default: '' },
		needHelp: { type: 'string', default: '' },
		dates: { type: 'array', default: [] },
		templates: { type: 'array', default: [] },
	},
	edit: ( { attributes, setAttributes } ) => {
		const {
			announcements,
			howItWorks,
			instructorName,
			instructorInitials,
			instructorCred,
			instructorBio,
			completeRequires,
			timeCommitment,
			needHelp,
			dates,
			templates,
		} = attributes;

		return (
			<div style={ { padding: '14px', border: '1px dashed #999' } }>
				<p>
					<strong>Course Panels</strong> — preview not available, please view on the front end.
				</p>

				{ /* ANNOUNCEMENTS */ }
				<Repeater
					label="Announcements"
					items={ announcements }
					onChange={ ( next ) => setAttributes( { announcements: next } ) }
					newRow={ { name: '', tag: '', time: '', body: '', pinned: false } }
					renderRow={ ( row, index, updateRow ) => (
						<>
							<TextControl label="Name" value={ row.name } onChange={ ( name ) => updateRow( index, { name } ) } />
							<TextControl label="Tag" value={ row.tag } onChange={ ( tag ) => updateRow( index, { tag } ) } />
							<TextControl label="Time" value={ row.time } onChange={ ( time ) => updateRow( index, { time } ) } />
							<TextareaControl label="Body" value={ row.body } onChange={ ( body ) => updateRow( index, { body } ) } />
							<CheckboxControl
								label="Pinned"
								checked={ !! row.pinned }
								onChange={ ( pinned ) => updateRow( index, { pinned } ) }
							/>
						</>
					) }
				/>

				{ /* SYLLABUS */ }
				<div style={ sectionStyle }>
					<h3>Syllabus — Instructor</h3>
					<TextControl label="Name" value={ instructorName } onChange={ ( v ) => setAttributes( { instructorName: v } ) } />
					<TextControl label="Avatar Initials" value={ instructorInitials } onChange={ ( v ) => setAttributes( { instructorInitials: v } ) } />
					<TextControl label="Credential Line" value={ instructorCred } onChange={ ( v ) => setAttributes( { instructorCred: v } ) } />
					<TextareaControl label="Bio" value={ instructorBio } onChange={ ( v ) => setAttributes( { instructorBio: v } ) } />
				</div>

				<div style={ sectionStyle }>
					<h3>Syllabus — How This Course Works</h3>
					<TextareaControl value={ howItWorks } onChange={ ( v ) => setAttributes( { howItWorks: v } ) } />
				</div>

				<Repeater
					label={ 'Syllabus — What "Complete" Requires' }
					items={ completeRequires }
					onChange={ ( next ) => setAttributes( { completeRequires: next } ) }
					newRow=""
					renderRow={ ( row, index, updateRow ) => (
						<TextControl
							label={ `Bullet ${ index + 1 }` }
							value={ row }
							onChange={ ( value ) => updateRow( index, value ) }
						/>
					) }
				/>

				<div style={ sectionStyle }>
					<h3>Syllabus — Time Commitment</h3>
					<TextareaControl value={ timeCommitment } onChange={ ( v ) => setAttributes( { timeCommitment: v } ) } />
				</div>

				<div style={ sectionStyle }>
					<h3>Syllabus — Need Help?</h3>
					<TextControl value={ needHelp } onChange={ ( v ) => setAttributes( { needHelp: v } ) } />
				</div>

				{ /* KEY DATES */ }
				<Repeater
					label="Key Dates"
					items={ dates }
					onChange={ ( next ) => setAttributes( { dates: next } ) }
					newRow={ { title: '', start: '', end: '', location: '' } }
					renderRow={ ( row, index, updateRow ) => (
						<>
							<TextControl label="Title" value={ row.title } onChange={ ( title ) => updateRow( index, { title } ) } />
							<TextControl
								label="Start"
								type="datetime-local"
								value={ row.start }
								onChange={ ( start ) => updateRow( index, { start } ) }
							/>
							<TextControl
								label="End"
								type="datetime-local"
								value={ row.end }
								onChange={ ( end ) => updateRow( index, { end } ) }
							/>
							<TextControl label="Location" value={ row.location } onChange={ ( location ) => updateRow( index, { location } ) } />
						</>
					) }
				/>

				{ /* DISCUSSION — not editable yet, comments coming later */ }
				<div style={ sectionStyle }>
					<h3>Discussion</h3>
					<p>Static placeholder view on front end.</p>
				</div>

				{ /* TEMPLATES */ }
				<Repeater
					label="Templates"
					items={ templates }
					onChange={ ( next ) => setAttributes( { templates: next } ) }
					newRow={ { name: '', fileUrl: '', fileType: '' } }
					renderRow={ ( row, index, updateRow ) => (
						<>
							<TextControl label="Name" value={ row.name } onChange={ ( name ) => updateRow( index, { name } ) } />
							<MediaUploadCheck>
								<MediaUpload
									onSelect={ ( media ) =>
										updateRow( index, {
											fileUrl: media.url,
											fileType: media.subtype || media.mime,
										} )
									}
									render={ ( { open } ) => (
										<Button variant="secondary" onClick={ open }>
											{ row.fileUrl ? 'Replace File' : 'Select File' }
										</Button>
									) }
								/>
							</MediaUploadCheck>
							{ row.fileUrl && (
								<span style={ { fontSize: '12px', color: '#555' } }>
									{ row.fileUrl.split( '/' ).pop() }
								</span>
							) }
						</>
					) }
				/>
			</div>
		);
	},
	save: () => null,
} );
