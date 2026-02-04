INSERT INTO `sys_file_reference` (
		`uid`, `pid`, `tstamp`, `crdate`,
		`deleted`, `hidden`, `sorting_foreign`,
		`tablenames`, `fieldname`, `uid_foreign`,
		`uid_local`, `crop`,
		`title`, `description`, `alternative`
) VALUES
-- Frontend user profile images
(1, 19, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'fe_users', 'image', 1,
4,
'', 'Profile picture of John Doe', 'John Doe in a professional setting', ''),
(2, 19, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'fe_users', 'image', 3,
5,
'', 'Profile picture of Alice Johnson', 'Dr. Alice Marie Johnson at a conference', ''),
(3, 19, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'fe_users', 'image', 4,
6,
'', 'Profile picture of Bob Brown', 'Bob William Brown during a business meeting', ''),

-- News media images (fal_media field)
-- News 1: New Product Launch
(4, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 1,
4,
'', 'Product Launch Event', 'New product being unveiled at launch event', ''),

-- News 5: Research Breakthrough
(5, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 5,
5,
'', 'Research Laboratory', 'Scientists working in the research lab', ''),

-- News 7: Digital Transformation
(6, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 7,
6,
'', 'Digital Infrastructure', 'Modern digital infrastructure and technology', ''),

-- News 14: Innovation Lab
(7, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 14,
4,
'', 'Innovation Lab Facility', 'State-of-the-art innovation lab interior', ''),

-- News 18: Employee Wellness
(8, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 18,
5,
'', 'Wellness Program', 'Employees participating in wellness activities', ''),

-- News 23: Data Analytics Platform
(9, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 23,
6,
'', 'Analytics Dashboard', 'Data analytics platform interface', ''),

-- News 26: AI Integration
(10, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 26,
4,
'', 'AI Technology', 'Artificial intelligence visualization', ''),

-- News 30: Green Energy Initiative
(11, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 30,
5,
'', 'Renewable Energy', 'Solar panels and wind turbines', ''),

-- News 33: Cloud Migration
(12, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 33,
6,
'', 'Cloud Infrastructure', 'Cloud computing data center', ''),

-- News 37: Automated Manufacturing
(13, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 37,
4,
'', 'Manufacturing Line', 'Automated manufacturing robots at work', ''),

-- News 42: Strategic Vision
(14, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 42,
5,
'', 'Leadership Meeting', 'Executive team discussing strategy', ''),

-- News 54: Innovation Showcase (multiple images)
(15, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 54,
4,
'', 'Innovation Showcase - Main Stage', 'Main presentation at innovation showcase', ''),
(16, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 2,
'tx_news_domain_model_news', 'fal_media', 54,
5,
'', 'Innovation Showcase - Demos', 'Product demonstrations at the event', ''),

-- News 57: Trade Show
(17, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 57,
6,
'', 'Trade Show Booth', 'Company booth at the trade show', ''),

-- News 60: Future Plans
(18, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 60,
4,
'', 'Future Vision', 'Conceptual image of future plans', ''),

-- German translations with same images
-- News 61 (German translation of 1)
(19, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 61,
4,
'', 'Produkteinführung', 'Neues Produkt wird bei der Veranstaltung vorgestellt', ''),

-- News 65 (German translation of 5)
(20, 15, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
0, 0, 1,
'tx_news_domain_model_news', 'fal_media', 65,
5,
'', 'Forschungslabor', 'Wissenschaftler arbeiten im Labor', '');
