import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'billplz_data', {} );

const defaultLabel = __( 'Billplz', 'bfw' );

const label = decodeEntities( settings.title ) || defaultLabel;
const icon = decodeEntities( settings.icon || '' );

// Content component
const Content = () => {
	return decodeEntities( settings.description || '' );
};

// Icon component
const Icon = ( props ) => {
	return (
		icon && <img style={{ position: 'absolute', right: '16px' }} src={ icon } alt={ label } />
	);
}

// Label component
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;

	return <PaymentMethodLabel text={
		<>
			<span style={{ width: '100%', display: 'flex' }}>
				{ label }
				<Icon />
			</span>
		</>
	} />;
};

// Payment method config object
const Billplz = {
	name: settings.name,
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod( Billplz );
