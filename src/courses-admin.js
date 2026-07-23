import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText } from '@wordpress/block-editor';

// Placeholder block to confirm the build/enqueue pipeline works end-to-end
// before the real course-panels/announcement/key-date/etc. blocks are built.
registerBlockType( 'illiana/course-panels-test', {
	title: 'Course Panels (test)',
	icon: 'welcome-learn-more',
	category: 'widgets',
	attributes: {
		content: { type: 'string', source: 'html', selector: 'p', default: 'hello' },
	},
	edit: ( { attributes, setAttributes } ) => {
		const blockProps = useBlockProps();
		return (
			<div { ...blockProps }>
				<RichText
					tagName="p"
					value={ attributes.content }
					onChange={ ( content ) => setAttributes( { content } ) }
					placeholder="Course panels block is wired up."
				/>
			</div>
		);
	},
	save: ( { attributes } ) => {
		return <RichText.Content tagName="p" value={ attributes.content } />;
	},
} );
