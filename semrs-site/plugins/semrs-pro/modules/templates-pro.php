<?php
/**
 * Pro starter sites for the SEMRS Blocks templates library: Consultant and
 * Clinic, plus two Pro sections (services in tabs, latest articles slider).
 *
 * @package semrs-pro
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'semrs_blocks_template_sites' ) ) {
	return;
}

/**
 * Pro section folder.
 *
 * @param array $dirs Folders.
 * @return array
 */
function semrs_pro_template_dirs( $dirs ) {
	$dirs[] = SEMRS_PRO_DIR . 'templates/sections/';
	return $dirs;
}
add_filter( 'semrs_blocks_template_section_dirs', 'semrs_pro_template_dirs' );

/**
 * Pro starter sites.
 *
 * @param array $sites Sites.
 * @return array
 */
function semrs_pro_template_sites( $sites ) {
	$common = array(
		'tier'        => 'pro',
		'preview_dir' => array( SEMRS_PRO_DIR . 'templates/previews/', SEMRS_PRO_URL . 'templates/previews/' ),
		'image_dir'   => SEMRS_PRO_DIR . 'templates/photos/',
		'image_base'  => SEMRS_PRO_URL . 'templates/photos/',
	);

	$sites['consultant'] = $common + array(
		'title'       => 'Consultant',
		'category'    => 'Business',
		'description' => 'Coach or consultant: personal brand, services in tabs, booking and latest articles.',
		'kind'        => 'services',
		'header'      => 'classic',
		'demo_name'   => 'Clear Path Consulting',
		'palette'     => array( 'primary' => '#1e293b', 'accent' => '#c8a96a', 'soft' => '#f8f3ea' ),
		'posts'       => semrs_blocks_template_posts(
			array(
				array( 'Setting goals for the next 90 days', 'Ninety days is long enough to make progress and short enough to stay focused.', array( array( 'Pick three goals', 'More than three usually means none get finished.' ), array( 'Make them measurable', 'Decide how you will know each goal is done.' ), array( 'Review every week', 'A short weekly review keeps the plan on track.' ) ) ),
				array( 'Is your offer clear enough?', 'Customers buy when they quickly understand what you do, for whom and what it costs.', array( array( 'Say who it is for', 'A specific audience makes your message stronger.' ), array( 'Explain the result', 'Describe what changes for the customer.' ), array( 'Show the next step', 'Make it easy to book, buy or ask a question.' ) ) ),
				array( 'Running better team meetings', 'Short meetings with a clear purpose save time and lead to decisions.', array( array( 'Share an agenda', 'Send topics and the goal before the meeting.' ), array( 'Keep it short', 'Thirty minutes is enough for most meetings.' ), array( 'End with actions', 'Agree who does what by when.' ) ) ),
			),
			'Business advice'
		),
		'images'      => semrs_blocks_template_photo_set( 'consultant' ),
		'copy'        => array(
			'hero_eyebrow'     => '{{name}} · Consultant',
			'hero_title'       => 'Clear advice that moves your business forward',
			'hero_text'        => 'One-to-one consulting for owners who want a practical plan, honest feedback and someone to keep them on track.',
			'hero_alt'         => 'Consultant talking with a client',
			'cta_text'         => 'Book a call',
			'cta_url'          => '/contact/',
			'cta2_text'        => 'How I help',
			'cta2_url'         => '/services/',
			'features_eyebrow' => 'How I help',
			'features_title'   => 'Services',
			'f1_icon'          => 'target',
			'f1_title'         => 'Strategy',
			'f1_text'          => 'Together we set clear goals, choose what to stop doing, and agree a 90-day plan you can follow.',
			't1_points'        => "Goal and priority setting\n90-day action plan\nMonthly check-ins\nWritten summary after every session",
			'f2_icon'          => 'chart',
			'f2_title'         => 'Marketing',
			'f2_text'          => 'Find the channels that bring your best customers and drop the ones that do not.',
			't2_points'        => "Customer and offer review\nWebsite and SEO review\nCampaign plan\nSimple tracking set-up",
			'f3_icon'          => 'users',
			'f3_title'         => 'Coaching',
			'f3_text'          => 'Regular sessions to build confidence, make decisions faster and lead your team.',
			't3_points'        => "Weekly or fortnightly sessions\nEmail support between calls\nPractical exercises\nProgress reviews",
			'about_eyebrow'    => 'About',
			'about_title'      => 'Hi, I am the person behind {{name}}',
			'about_text'       => 'I help small business owners turn good ideas into simple plans and steady results.',
			'about_alt'        => 'Consultant leading a team meeting',
			'about_points'     => "Practical, jargon-free advice\nFlexible online sessions\nClear plans you own",
			'steps_eyebrow'    => 'Working together',
			'steps_title'      => 'How it works',
			's1_icon'          => 'chat',
			's1_title'         => 'Free intro call',
			's1_text'          => 'A short call to understand where you are and where you want to go.',
			's2_icon'          => 'lightbulb',
			's2_title'         => 'Your plan',
			's2_text'          => 'A written plan with priorities, timings and what success looks like.',
			's3_icon'          => 'rocket',
			's3_title'         => 'Ongoing support',
			's3_text'          => 'Regular sessions to review progress and adjust the plan.',
			'posts_title'      => 'Latest articles',
			'faq_eyebrow'      => 'Questions',
			'faq_title'        => 'Frequently asked questions',
			'q1'               => 'Are sessions online or in person?',
			'a1'               => 'Online by default, in person in {{city}} when it helps.',
			'q2'               => 'How long is each session?',
			'a2'               => 'Usually 60 minutes, with a short written summary afterwards.',
			'q3'               => 'Is there a minimum commitment?',
			'a3'               => 'No. Book single sessions or a monthly package.',
			'q4'               => 'Who do you work with?',
			'a4'               => 'Owners and managers of small and growing businesses.',
			'cta_title'        => 'Ready for a clear plan?',
			'cta_band_text'    => 'Book a free 20-minute intro call.',
			'contact_title'    => 'Book a call',
			'contact_text'     => 'Tell me a little about your business and what you would like help with.',
			'contact_points'   => "Free intro call\nReply within one business day",
			'form_services'    => "Strategy\nMarketing\nCoaching\nSomething else",
			'text_title'       => 'My approach',
			'text_p1'          => 'Good advice is simple, specific and something you can act on this week.',
			'text_p2'          => 'Every session ends with clear next steps, so you always know what to do next.',
		),
		'pages'       => array(
			'home'     => array( 'title' => 'Home', 'front' => true, 'sections' => array( 'hero', 'services-tabs', 'about', 'steps', 'posts-slider', 'cta' ) ),
			'services' => array( 'title' => 'Services', 'sections' => array( 'page-header', 'services-tabs', 'steps', 'faq', 'cta' ), 'vars' => array( 'page_title' => 'Services', 'page_intro' => 'Strategy, marketing and coaching for small business owners.' ) ),
			'about'    => array( 'title' => 'About', 'sections' => array( 'page-header', 'about', 'text', 'steps', 'cta' ), 'vars' => array( 'page_title' => 'About me', 'page_intro' => 'How I work and who I help.' ) ),
			'blog'     => array( 'title' => 'Articles', 'posts_page' => true, 'vars' => array( 'page_title' => 'Articles', 'page_intro' => 'Practical advice for small business owners.' ) ),
			'contact'  => array( 'title' => 'Contact', 'sections' => array( 'page-header', 'contact', 'faq' ), 'vars' => array( 'page_title' => 'Book a call', 'page_intro' => 'Let us find out if we are a good fit.' ) ),
		),
	);

	$sites['clinic'] = $common + array(
		'title'       => 'Clinic',
		'category'    => 'Health',
		'description' => 'Dental or medical clinic: treatments, opening hours, areas served and appointment requests.',
		'kind'        => 'local',
		'header'      => 'topbar',
		'demo_name'   => 'Riverside Clinic',
		'palette'     => array( 'primary' => '#0c4a6e', 'accent' => '#7dd3fc', 'soft' => '#e0f2fe' ),
		'posts'       => semrs_blocks_template_posts(
			array(
				array( 'Preparing for your first appointment', 'A little preparation helps you get the most from your visit.', array( array( 'Bring your information', 'A list of medicines, allergies and recent test results.' ), array( 'Write down questions', 'It is easy to forget questions during the appointment.' ), array( 'Arrive a little early', 'This gives time to complete any forms.' ) ) ),
				array( 'Staying active as you get older', 'Regular, gentle activity supports strength, balance and wellbeing.', array( array( 'Walk every day', 'Short, regular walks are a great start.' ), array( 'Add balance exercises', 'Simple balance work can help prevent falls.' ), array( 'Ask for advice', 'Talk to us before starting a new exercise plan.' ) ) ),
				array( 'Simple steps to eat more healthily', 'Small, steady changes are easier to keep than strict diets.', array( array( 'Add vegetables', 'Fill half your plate with vegetables or salad.' ), array( 'Choose water', 'Swap sugary drinks for water.' ), array( 'Plan meals', 'Planning ahead makes healthy choices easier.' ) ) ),
			),
			'Health tips'
		),
		'images'      => semrs_blocks_template_photo_set( 'clinic' ),
		'copy'        => array(
			'hero_eyebrow'     => '{{name}} · {{city}}',
			'hero_title'       => 'Gentle, modern care for the whole family',
			'hero_text'        => 'Friendly appointments, clear treatment plans and prices explained before we start.',
			'hero_alt'         => 'Doctor holding a tablet',
			'cta_text'         => 'Request an appointment',
			'cta_url'          => '/contact/',
			'cta2_text'        => 'Treatments',
			'cta2_url'         => '/treatments/',
			'features_eyebrow' => 'Treatments',
			'features_title'   => 'How we can help',
			'features_text'    => 'Care for adults and children in a calm, welcoming clinic.',
			'f_url'            => '/treatments/',
			'f1_icon'          => 'heart',
			'f1_title'         => 'Check-ups',
			'f1_text'          => 'Regular check-ups and cleaning to keep problems small.',
			't1_points'        => "Full examination\nCleaning\nPersonal care advice\nReminders by email",
			'f2_icon'          => 'star',
			'f2_title'         => 'Treatments',
			'f2_text'          => 'Fillings, root care and repairs explained step by step.',
			't2_points'        => "Clear treatment plan\nPrices before we start\nComfort-focused care\nFollow-up visit",
			'f3_icon'          => 'users',
			'f3_title'         => 'Family care',
			'f3_text'          => 'Patient, friendly appointments for children and nervous patients.',
			't3_points'        => "Child-friendly visits\nExtra time when needed\nAdvice for parents\nEvening appointments",
			'about_eyebrow'    => 'About {{name}}',
			'about_title'      => 'A calm clinic with a caring team',
			'about_text'       => 'We take time to listen, explain your options and agree a plan that suits you.',
			'about_alt'        => 'Doctor and nurse with a patient',
			'about_points'     => "Clear explanations\nPrices agreed in advance\nModern equipment\nEasy online requests",
			'hours_title'      => 'Opening hours',
			'hours_week'       => '9:00 – 19:00',
			'hours_sat'        => '9:00 – 13:00',
			'hours_sun'        => 'Closed',
			'areas_title'      => 'Patients from',
			'areas'            => "{{city}}\nCity centre\nNorth district\nSouth district",
			'faq_eyebrow'      => 'Questions',
			'faq_title'        => 'Frequently asked questions',
			'q1'               => 'Are you taking new patients?',
			'a1'               => 'Yes. Send an appointment request and we will suggest the first available times.',
			'q2'               => 'What should I bring?',
			'a2'               => 'Any recent records or medication list, and arrive five minutes early.',
			'q3'               => 'Do you treat children?',
			'a3'               => 'Yes, we welcome children of all ages.',
			'q4'               => 'Do you explain costs first?',
			'a4'               => 'Always. You get a written estimate before any treatment.',
			'cta_title'        => 'Book your next visit',
			'cta_band_text'    => 'Request an appointment online or call {{phone}}.',
			'contact_title'    => 'Request an appointment',
			'contact_text'     => 'Send your preferred days and times. This form is for appointments only, not urgent medical advice.',
			'contact_points'   => "We reply during opening hours\nYour details stay private",
			'form_services'    => "Check-up\nTreatment\nChild appointment\nOther",
			'text_title'       => 'Our clinic',
			'text_p1'          => '{{name}} has cared for patients in {{city}} with a simple promise: listen first, explain clearly, treat gently.',
			'text_p2'          => 'Our team keeps learning so you get modern, comfortable care.',
		),
		'pages'       => array(
			'home'       => array( 'title' => 'Home', 'front' => true, 'sections' => array( 'hero', 'features', 'about', 'hours', 'posts', 'faq', 'cta' ) ),
			'treatments' => array( 'title' => 'Treatments', 'sections' => array( 'page-header', 'services-tabs', 'steps', 'faq', 'cta' ), 'vars' => array( 'page_title' => 'Treatments', 'page_intro' => 'Care for adults and children, explained clearly.' ) ),
			'about'      => array( 'title' => 'About', 'sections' => array( 'page-header', 'about', 'text', 'cta' ), 'vars' => array( 'page_title' => 'About the clinic', 'page_intro' => 'Our team and our approach.' ) ),
			'blog'       => array( 'title' => 'Health tips', 'posts_page' => true, 'vars' => array( 'page_title' => 'Health tips', 'page_intro' => 'Advice and news from {{name}}.' ) ),
			'contact'    => array( 'title' => 'Contact', 'sections' => array( 'page-header', 'contact', 'hours' ), 'vars' => array( 'page_title' => 'Appointments & contact', 'page_intro' => 'Request an appointment or find our opening hours.' ) ),
		),
	);
	foreach ( semrs_pro_template_niches() as $n ) {
		$sites[ $n['id'] ] = semrs_blocks_niche_site( $n + array( 'tier' => 'pro', 'photo_dir' => SEMRS_PRO_DIR . 'templates/photos/', 'photo_base' => SEMRS_PRO_URL . 'templates/photos/' ) );
	}
	return $sites;
}
add_filter( 'semrs_blocks_template_sites', 'semrs_pro_template_sites' );

/**
 * Pro niche sites (expanded by semrs_blocks_niche_site()).
 *
 * @return array
 */
function semrs_pro_template_niches() {
	return array(
		array(
			'id' => 'travel-agency', 'title' => 'Travel Agency', 'category' => 'Travel', 'kind' => 'services',
			'description'   => 'Travel agency or tour operator: tours, destinations, enquiry form and travel blog.',
			'hero_title'    => 'Trips planned around you',
			'hero_text'     => 'Tailor-made holidays, group tours and city breaks with one friendly point of contact.',
			'cta_text'      => 'Plan my trip', 'cta2_text' => 'Tours & services', 'features_title' => 'Tours & travel services',
			'services'      => array( array( 'globe', 'Tailor-made trips', 'Itineraries built around your dates and budget.' ), array( 'users', 'Group tours', 'Guided tours with small, friendly groups.' ), array( 'home', 'Hotels & stays', 'Hand-picked places to stay.' ), array( 'calendar', 'City breaks', 'Short trips planned in a few clicks.' ), array( 'shield', 'Travel support', 'Help before and during your trip.' ), array( 'gift', 'Honeymoons', 'Special trips for special moments.' ) ),
			'about_title'   => 'Travel experts who listen',
			'about_text'    => 'We ask about what you love, then plan every detail so you can simply enjoy the trip.',
			'about_points'  => array( 'One point of contact', 'Clear, upfront prices', 'Support while you travel' ),
			'faq'           => array( array( 'Can you plan a custom trip?', 'Yes, most of our trips are tailor-made.' ), array( 'Do you help with visas?', 'We share guidance and documents lists; requirements depend on your passport.' ), array( 'How do I pay?', 'A deposit to confirm, then the balance before travel.' ), array( 'What if plans change?', 'Tell us and we will help with changes under the booking terms.' ) ),
			'form_services' => array( 'Tailor-made trip', 'Group tour', 'City break', 'Honeymoon' ),
			'blog_title'    => 'Travel guides',
			'demo_name'     => 'Horizon Travel', 'palette' => array( 'primary' => '#0b3954', 'accent' => '#ffd166', 'soft' => '#fff7e0' ),
			'hero_alt'      => 'Couple walking on a hillside at sunset', 'about_alt' => 'Planning a trip with a map and travel items', 'blog_cat' => 'Travel guides',
			'posts'         => array(
				array( 'Choosing a beach holiday destination', 'Weather, travel time and what you want to do narrow the choice quickly.', array( array( 'Check the season', 'Rainy seasons and peak prices vary a lot between destinations.' ), array( 'Think about travel time', 'Long flights need a longer stay to be worth it.' ), array( 'Match your style', 'Quiet coves, busy resorts or islands to explore: decide first.' ) ) ),
				array( 'Planning a lakes and mountains trip', 'Mountain trips are best planned around the weather and how active you want to be.', array( array( 'Pick a base', 'One or two bases save packing and long drives.' ), array( 'Plan easy and hard days', 'Mix big hikes with lake days and scenic drives.' ), array( 'Pack layers', 'Mountain weather changes quickly, even in summer.' ) ) ),
				array( 'Airport checklist before you fly', 'A short checklist avoids most travel-day stress.', array( array( 'Documents', 'Passport validity, visas and booking confirmations.' ), array( 'Luggage rules', 'Check the weight and liquid limits for your airline.' ), array( 'Arrive on time', 'Leave extra time for security at busy airports.' ) ) ),
			),
		),
		array(
			'id' => 'camping', 'title' => 'Camping & Outdoors', 'category' => 'Travel', 'kind' => 'local',
			'description'   => 'Campsite, glamping or outdoor adventures: pitches, activities, hours and booking requests.',
			'hero_eyebrow'  => 'Camping near {{city}}',
			'hero_title'    => 'Wake up in nature',
			'hero_text'     => 'Tent pitches, cosy cabins and outdoor activities for families, friends and solo adventurers.',
			'cta_text'      => 'Request a booking', 'cta2_text' => 'Stays & activities', 'features_title' => 'Stays & activities',
			'services'      => array( array( 'home', 'Tent pitches', 'Grass pitches with space to relax.' ), array( 'star', 'Glamping cabins', 'Comfortable cabins with beds and lighting.' ), array( 'users', 'Group camping', 'Space for schools, clubs and families.' ), array( 'globe', 'Guided hikes', 'Trails for every fitness level.' ), array( 'heart', 'Campfire evenings', 'Fire pits and evening activities.' ), array( 'shield', 'Facilities', 'Showers, toilets and safe parking.' ) ),
			'about_title'   => 'Outdoor stays done simply',
			'about_text'    => 'A quiet, well-kept site with friendly hosts and plenty to explore nearby.',
			'about_points'  => array( 'Family and pet friendly', 'Clean facilities', 'Activities nearby' ),
			'faq'           => array( array( 'Are pets allowed?', 'Yes, well-behaved dogs on leads are welcome.' ), array( 'Can I hire equipment?', 'Ask us about tents and gear when you request a booking.' ), array( 'What time is check-in?', 'From 14:00; check-out by 11:00.' ), array( 'Are campfires allowed?', 'Yes, in the fire pits provided.' ) ),
			'form_services' => array( 'Tent pitch', 'Glamping cabin', 'Group booking', 'Activity' ),
			'hours_week'    => 'Reception 8:00 – 20:00', 'hours_sat' => '8:00 – 20:00', 'hours_sun' => '8:00 – 18:00',
			'demo_name'     => 'Pine Ridge Camping', 'palette' => array( 'primary' => '#1b4332', 'accent' => '#e9c46a', 'soft' => '#f4f1de' ),
			'hero_alt'      => 'Tent pitched with a view over hills and mist', 'about_alt' => 'Campers holding hot drinks outdoors', 'blog_cat' => 'Outdoor tips',
			'posts'         => array(
				array( 'Beginner hiking tips', 'Start with shorter, well-marked trails and build up from there.', array( array( 'Choose the right trail', 'Check the distance, climb and how long it usually takes.' ), array( 'Wear good shoes', 'Comfortable, broken-in shoes with grip prevent blisters and slips.' ), array( 'Tell someone your plan', 'Share your route and expected return time.' ) ) ),
				array( 'What to pack for your first camping trip', 'A short list of essentials makes the first trip comfortable.', array( array( 'Sleep well', 'Tent, sleeping bag rated for the season and a sleeping mat.' ), array( 'Cook simply', 'A small stove, a pan and easy meals.' ), array( 'Stay dry and warm', 'Waterproof jacket, warm layers and spare socks.' ) ) ),
				array( 'Campervan camping: a quick guide', 'Campervans make camping easy, with a few extra things to plan.', array( array( 'Book a pitch with hook-up', 'Electric hook-up keeps batteries charged.' ), array( 'Level the van', 'Use levelling blocks for a comfortable night.' ), array( 'Leave no trace', 'Use the site facilities for waste and water.' ) ) ),
			),
		),
		array(
			'id' => 'school', 'title' => 'School & Admissions', 'category' => 'Education', 'kind' => 'education',
			'description'   => 'School, college or academy: programs, admissions process and registration form.',
			'hero_title'    => 'A school where every student can thrive',
			'hero_text'     => 'Caring teachers, modern learning and a clear path from admission to graduation.',
			'cta_text'      => 'Apply for admission', 'cta_url' => '/admissions/', 'cta2_text' => 'Our programs',
			'services'      => array( array( 'home', 'Primary', 'A warm start with strong reading and maths foundations.' ), array( 'award', 'Secondary', 'Broad subjects and exam preparation.' ), array( 'lightbulb', 'Science & tech', 'Labs, coding and project-based learning.' ), array( 'users', 'Sports & clubs', 'Teams, arts and clubs after class.' ), array( 'globe', 'Languages', 'Language programs for every level.' ), array( 'heart', 'Student care', 'Guidance and wellbeing support.' ) ),
			'about_title'   => 'Learning with care',
			'about_text'    => 'Small classes, experienced teachers and regular updates for parents.',
			'about_points'  => array( 'Small class sizes', 'Regular parent updates', 'Safe, modern campus' ),
			'steps'         => array( array( 'pen', 'Register interest', 'Send the registration form below.' ), array( 'calendar', 'Visit & assessment', 'Meet us and complete a short assessment.' ), array( 'check-circle', 'Confirm your place', 'Receive your offer and complete enrolment.' ) ),
			'steps_title'   => 'How admissions work',
			'faq'           => array( array( 'When do admissions open?', 'Applications are accepted all year, subject to places.' ), array( 'What documents are needed?', 'Usually a birth certificate, previous school records and photos.' ), array( 'Can we visit the school?', 'Yes, request a visit through the registration form.' ), array( 'Do you offer transport?', 'Ask us about routes in your area.' ) ),
			'form_services' => array( 'Primary', 'Secondary', 'Science & tech', 'Languages' ),
			'admissions_title' => 'Admissions & registration',
			'demo_name'     => 'Oakwood School', 'palette' => array( 'primary' => '#1e3a8a', 'accent' => '#fbbf24', 'soft' => '#fef6dc' ),
			'hero_alt'      => 'Students learning in a classroom', 'about_alt' => 'Students working together at a table', 'blog_cat' => 'School news',
			'posts'         => array(
				array( 'Building a reading habit at home', 'Ten to twenty minutes a day makes a real difference to reading confidence.', array( array( 'Read together', 'Take turns reading pages and talk about the story.' ), array( 'Let children choose', 'Interest matters more than the level of the book.' ), array( 'Visit the library', 'Regular visits keep reading fresh and free.' ) ) ),
				array( 'How parents can support learning', 'Simple routines and interest in school life help children do well.', array( array( 'Ask open questions', 'Ask what they enjoyed or found hard today.' ), array( 'Set a homework spot', 'A quiet, regular place helps focus.' ), array( 'Stay in touch with teachers', 'Share any concerns early.' ) ) ),
				array( 'Study tips before exams', 'Spreading revision out works better than last-minute cramming.', array( array( 'Make a plan', 'Break subjects into small topics across the weeks.' ), array( 'Test yourself', 'Practice questions show what you really know.' ), array( 'Rest and sleep', 'Breaks and sleep help memory.' ) ) ),
			),
		),
		array(
			'id' => 'online-courses', 'title' => 'Online Courses', 'category' => 'Education', 'kind' => 'education',
			'description'   => 'Online academy or course creator: courses, enrolment, student registration and blog.',
			'hero_title'    => 'Learn new skills online, at your own pace',
			'hero_text'     => 'Practical courses with video lessons, exercises and certificates of completion.',
			'cta_text'      => 'Enrol now', 'cta_url' => '/admissions/', 'cta2_text' => 'Browse courses', 'features_title' => 'Popular courses',
			'services'      => array( array( 'code', 'Web development', 'Build websites step by step.' ), array( 'chart', 'Digital marketing', 'SEO, ads and social media basics.' ), array( 'pen', 'Writing', 'Clear writing for work and the web.' ), array( 'camera', 'Design', 'Design fundamentals and tools.' ), array( 'briefcase', 'Business', 'Start and grow a small business.' ), array( 'award', 'Certificates', 'Earn a certificate when you finish.' ) ),
			'about_title'   => 'Courses built by practitioners',
			'about_text'    => 'Every course is taught by people who do the work, with exercises you can use right away.',
			'about_points'  => array( 'Learn at your own pace', 'Lifetime access to lessons', 'Certificate of completion' ),
			'steps'         => array( array( 'search', 'Choose a course', 'Browse courses and pick your level.' ), array( 'pen', 'Register', 'Complete the short registration form.' ), array( 'play', 'Start learning', 'Watch lessons and practise on your schedule.' ) ),
			'faq'           => array( array( 'Do courses have deadlines?', 'No, learn at your own pace.' ), array( 'Do I get a certificate?', 'Yes, when you complete all lessons.' ), array( 'Is there a refund policy?', 'Yes, see our terms for details.' ), array( 'Do I need experience?', 'Each course lists the level and what you need.' ) ),
			'form_services' => array( 'Web development', 'Digital marketing', 'Writing', 'Design', 'Business' ),
			'admissions_title' => 'Student registration', 'admissions_intro' => 'Register for a course and we will send your access details.',
			'demo_name'     => 'SkillUp Academy', 'palette' => array( 'primary' => '#312e81', 'accent' => '#a5b4fc', 'soft' => '#eef2ff' ),
			'hero_alt'      => 'Student learning online on a laptop at home', 'about_alt' => 'Learner on a video call at a desk', 'blog_cat' => 'Learning tips',
			'posts'         => array(
				array( 'How to stay motivated in an online course', 'Small goals and a fixed schedule help you finish what you start.', array( array( 'Set a weekly time', 'Book learning time in your calendar like a meeting.' ), array( 'Finish one lesson at a time', 'Short wins keep you going.' ), array( 'Practise straight away', 'Use what you learn in a small project.' ) ) ),
				array( 'Taking notes that help you remember', 'Good notes are short, in your own words and reviewed later.', array( array( 'Summarise, do not copy', 'Write the main idea in a sentence or two.' ), array( 'Add examples', 'Examples make ideas easier to recall.' ), array( 'Review weekly', 'A quick review moves ideas into long-term memory.' ) ) ),
				array( 'Getting the most from live sessions', 'Live sessions are a chance to ask questions and learn from others.', array( array( 'Prepare a question', 'Bring one thing you found hard.' ), array( 'Join on time', 'The first minutes often explain the plan.' ), array( 'Watch the recording', 'Rewatch parts you want to practise.' ) ) ),
			),
		),
		array(
			'id' => 'ai-llm', 'title' => 'AI & LLM Services', 'category' => 'Technology', 'kind' => 'services',
			'description'   => 'AI consultancy: chatbots, LLM integration, automation and AI search (GEO) optimisation.',
			'hero_title'    => 'Practical AI for your business',
			'hero_text'     => 'Chatbots, automations and LLM integrations that save time, with privacy and accuracy built in.',
			'cta_text'      => 'Book an AI consultation', 'cta2_text' => 'AI services',
			'services'      => array( array( 'chat', 'AI chatbots', 'Assistants trained on your own help content.' ), array( 'bolt', 'Automation', 'Workflows that remove repetitive tasks.' ), array( 'code', 'LLM integration', 'Connect language models to your apps and data.' ), array( 'search', 'AI search (GEO)', 'Help AI assistants find and cite your content, with llms.txt.' ), array( 'shield', 'Privacy & safety', 'Data protection and human review built in.' ), array( 'lightbulb', 'AI strategy', 'Find where AI helps and where it does not.' ) ),
			'about_title'   => 'AI that is useful, not hype',
			'about_text'    => 'We start with your problem, test small and measure results before scaling.',
			'about_points'  => array( 'Privacy-first solutions', 'Human review of outputs', 'Clear costs and results' ),
			'faq'           => array( array( 'Is our data safe?', 'We design for privacy and only use providers that meet your requirements.' ), array( 'Will AI replace our staff?', 'Our aim is to remove repetitive tasks so your team can focus on customers.' ), array( 'How accurate are chatbots?', 'We ground answers in your content and add human handover for anything uncertain.' ), array( 'What is GEO?', 'Generative engine optimisation: making your content easy for AI assistants to find and cite.' ) ),
			'form_services' => array( 'Chatbot', 'Automation', 'LLM integration', 'AI search (GEO)', 'Strategy' ),
			'demo_name'     => 'Northstar AI', 'palette' => array( 'primary' => '#0f172a', 'accent' => '#a78bfa', 'soft' => '#f3f0ff' ),
			'hero_alt'      => 'Developers working on code together', 'about_alt' => 'Reviewing data on a laptop', 'blog_cat' => 'AI guides',
			'posts'         => array(
				array( 'Where AI chatbots help most', 'Chatbots work best for repeated questions with clear answers.', array( array( 'Common questions', 'Opening hours, orders and policies are good first topics.' ), array( 'Ground it in your content', 'Answers should come from your own help pages.' ), array( 'Hand over to people', 'Uncertain or sensitive questions should go to your team.' ) ) ),
				array( 'Getting started with workflow automation', 'Start with one repetitive task and measure the time saved.', array( array( 'List repetitive tasks', 'Copying data, sending reminders and sorting emails.' ), array( 'Automate one step', 'Small automations are easier to test and trust.' ), array( 'Review results', 'Check the output regularly, especially at the start.' ) ) ),
				array( 'What is AI search optimisation (GEO)?', 'AI assistants answer questions by citing sources. Clear, structured content is easier to cite.', array( array( 'Answer questions directly', 'Put the answer near the top of the page.' ), array( 'Use structured data', 'Schema markup and llms.txt help machines understand your site.' ), array( 'Keep facts current', 'Update dates, prices and details regularly.' ) ) ),
			),
		),
	);
}
