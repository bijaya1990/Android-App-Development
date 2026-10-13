/**
 * Builds the {{placeholder}} values for a card from a person, the
 * organisation and the project, plus invented demo people for galleries.
 */

const DEMO = [
	[ 'Aarav Mehta', 'm' ], [ 'Ananya Iyer', 'f' ], [ 'Rohan Das', 'm' ], [ 'Priya Nair', 'f' ], [ 'Kabir Singh', 'm' ],
	[ 'Ishita Rao', 'f' ], [ 'Vihaan Gupta', 'm' ], [ 'Sara Thomas', 'f' ], [ 'Arjun Reddy', 'm' ], [ 'Meera Joshi', 'f' ],
	[ 'Aditya Kulkarni', 'm' ], [ 'Diya Banerjee', 'f' ], [ 'Kunal Verma', 'm' ], [ 'Neha Pillai', 'f' ], [ 'Yash Patel', 'm' ],
	[ 'Riya Chatterjee', 'f' ], [ 'Siddharth Menon', 'm' ], [ 'Tanvi Shah', 'f' ], [ 'Harsh Mishra', 'm' ], [ 'Pooja Hegde', 'f' ],
];
const CLASSES = [ 'VIII', 'IX', 'X', 'XI', 'XII', 'VI', 'VII' ];
const COURSES = [ 'B.Sc. Physics', 'B.Com', 'BCA', 'B.Tech CSE', 'MBA', 'B.A. English' ];
const DESIG = [ 'Senior Teacher', 'Accountant', 'Software Engineer', 'Lab Assistant', 'HR Executive', 'Nurse', 'Security Officer', 'Coordinator' ];
const DEPT = [ 'Science', 'Accounts', 'Engineering', 'Administration', 'Human Resources', 'Operations' ];
const BLOOD = [ 'B+', 'O+', 'A+', 'AB+', 'O-', 'A-' ];

export const DEMO_ORG = {
	org_name: 'Sunrise Public School',
	org_tagline: 'Affiliated to CBSE, New Delhi',
	org_address: '12, MG Road, Bhubaneswar, Odisha 751001',
	org_phone: '+91 98765 43210',
	org_email: 'office@sunrise.edu.in',
	org_website: 'www.sunrise.edu.in',
	signatory_name: 'Dr. R. K. Das',
	signatory_title: 'Principal',
	session: '2026-27',
};

export function defaultTerms( kind ) {
	if ( kind === 'student' ) {
		return 'This card is the property of the institution and must be carried at all times on campus. It is not transferable. Loss of the card must be reported immediately. If found, please return to the address below.';
	}
	return 'This card is the property of the organisation and must be shown on request. It is not transferable and must be returned when leaving. If found, please return to the address below.';
}

/** Certificate heading from its type, e.g. "Certificate of Merit". */
export function certificateTitle( subtypeName ) {
	const n = String( subtypeName || '' );
	const rules = [
		[ /completion|computer|course/i, 'Certificate of Completion' ],
		[ /participation|attendance|participant/i, 'Certificate of Participation' ],
		[ /merit|topper/i, 'Certificate of Merit' ],
		[ /winner/i, 'Certificate of Achievement' ],
		[ /internship/i, 'Internship Certificate' ],
		[ /training|workshop/i, 'Certificate of Training' ],
		[ /skill|typing/i, 'Skill Certificate' ],
		[ /sports/i, 'Sports Certificate' ],
		[ /character|conduct/i, 'Character Certificate' ],
		[ /membership/i, 'Certificate of Membership' ],
		[ /speaker|guest/i, 'Certificate of Honour' ],
		[ /volunteer|sevak|donor|blood/i, 'Certificate of Appreciation' ],
	];
	const hit = rules.find( ( r ) => r[ 0 ].test( n ) );
	return hit ? hit[ 1 ] : 'Certificate of Appreciation';
}

/** Card title from the sub-type, e.g. "Student ID Card". */
export function cardTitle( subtypeName, kind ) {
	if ( kind === 'certificate' ) {
		return certificateTitle( subtypeName );
	}
	if ( ! subtypeName ) {
		return kind === 'student' ? 'Student ID Card' : 'Identity Card';
	}
	const first = subtypeName.split( /[\/,]/ )[ 0 ].trim();
	if ( /pass|badge|card/i.test( first ) ) {
		return first;
	}
	return kind === 'other' ? `${ first } Pass` : `${ first } ID Card`;
}

/** Demo values for gallery previews. */
export function demoVars( kind, index = 0, org = {}, extra = {} ) {
	const [ name, gender ] = DEMO[ index % DEMO.length ];
	const year = new Date().getFullYear();
	const isCollege = /college|university/i.test( org.org_name || '' );
	const v = Object.assign( {}, DEMO_ORG, org, {
		name,
		gender,
		id_no: ( kind === 'student' ? 'STU' : 'EMP' ) + String( 1040 + index * 7 ),
		roll_no: String( 12 + index * 3 ),
		regd_no: `REG/${ year }/${ 400 + index * 13 }`,
		class: isCollege ? COURSES[ index % COURSES.length ] : CLASSES[ index % CLASSES.length ],
		section: isCollege ? '' : 'ABCD'[ index % 4 ],
		designation: kind === 'other' ? 'Visitor' : DESIG[ index % DESIG.length ],
		department: DEPT[ index % DEPT.length ],
		guardian: 'Mr. ' + name.split( ' ' )[ 1 ] + ' ' + [ 'Kumar', 'Prasad', 'Lal', 'Chand' ][ index % 4 ],
		dob: `${ String( 3 + ( index % 25 ) ).padStart( 2, '0' ) }-0${ 1 + ( index % 9 ) }-${ kind === 'student' ? 2011 - ( index % 6 ) : 1988 + ( index % 10 ) }`,
		blood_group: BLOOD[ index % BLOOD.length ],
		mobile: '98' + String( 76543210 + index * 1371 ).slice( 0, 8 ),
		email: name.toLowerCase().replace( /\s+/g, '.' ) + '@example.com',
		valid_until: `31-03-${ year + 1 }`,
		verify_url: ( window.PKC && window.PKC.home ? window.PKC.home : 'https://pikacart.in/' ) + 'verify/demo',
		photo: '',
	}, kind === 'certificate' ? {
		id_no: 'CERT-' + String( 1 + index ).padStart( 4, '0' ),
		course: [ 'Diploma in Computer Applications (DCA)', 'Annual Sports Meet', 'Science Exhibition', 'Tally Prime with GST', 'Inter-School Quiz' ][ index % 5 ],
		grade: [ 'Grade A+', 'First Prize', 'Grade A', 'Second Prize', 'Excellent' ][ index % 5 ],
		duration: `Jan ${ year } – Jun ${ year }`,
		cert_date: `15-08-${ year }`,
	} : {}, extra );
	return finishVars( v, kind );
}

/** Derived values every card needs. */
export function finishVars( v, kind ) {
	v.class_section = [ v.class, v.section ].filter( Boolean ).join( ' - ' );
	v.card_title = v.card_title || cardTitle( v.subtype_name, kind );
	// "Certificate of Merit" -> "of Merit", for designs that print "CERTIFICATE" large on its own.
	v.title_rest = v.title_rest || String( v.card_title ).replace( /\bcertificate\b/i, '' ).replace( /\s+/g, ' ' ).trim();
	v.terms = v.terms || defaultTerms( kind );
	v.return_line = v.return_line || 'If found, please return to:';
	return v;
}

/**
 * Values for a real person.
 * @param {object} member   { name, id_no, photo, data:{...}, valid_until, verify_token }
 * @param {object} org      Organisation fields from the app (org.name, org.logo...).
 * @param {object} project  { card_title, terms, custom:[{key,label}], session }
 */
export function memberVars( member, org, project = {}, kind = 'student' ) {
	const d = member.data || {};
	const v = Object.assign( {}, d, {
		name: member.name || d.name || '',
		id_no: member.id_no || d.id_no || '',
		photo: member.photo || '',
		valid_from: member.valid_from || d.valid_from || project.valid_from || '',
		valid_until: member.valid_until || d.valid_until || project.valid_until || '',
		org_name: org.name || '',
		org_tagline: org.tagline || '',
		org_address: org.address || '',
		org_phone: org.phone || '',
		org_email: org.org_email || '',
		org_website: org.website || '',
		signatory_name: org.signatory_name || '',
		signatory_title: org.signatory_title || '',
		event_name: org.event_name || '',
		session: member.session || project.session || org.session_year || '',
		logo: org.logo || '',
		sign: org.sign || '',
		seal: org.seal || '',
		terms: project.terms || org.terms || '',
		card_title: project.card_title || '',
		cert_date: d.cert_date || project.cert_date || '',
		subtype_name: project.subtype_name || '',
		verify_url: member.verify_token ? `${ window.PKC.home }verify/${ member.verify_token }` : `${ window.PKC.home }verify/demo`,
	} );
	( project.custom || [] ).forEach( ( c ) => {
		v[ `${ c.key }_label` ] = c.label;
	} );
	return finishVars( v, kind );
}

/** Kind of card for a sub-type, from its default fields. */
export function kindOf( subtype ) {
	if ( subtype && subtype.doc === 'certificate' ) {
		return 'certificate';
	}
	const fields = ( subtype && subtype.fields ) || [];
	const keys = fields.map( ( f ) => f.key );
	if ( keys.includes( 'class' ) ) {
		return 'student';
	}
	if ( ( subtype && /visitor|pass|guest|vip|delegate|attendant/i.test( subtype.name ) ) ) {
		return 'other';
	}
	return 'staff';
}
