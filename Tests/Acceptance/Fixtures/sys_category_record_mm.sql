-- Category relations for tx_news_domain_model_news records
-- Categories: 1=Politics, 2=Economy, 3=Sports, 4=Culture, 5=Science
-- Subcategories: 9=Finance, 10=Companies, 11=Labor Market, 18=Technology, 19=Medicine, 20=Environment, etc.

INSERT INTO `sys_category_record_mm` (`uid_local`, `uid_foreign`, `tablenames`, `fieldname`, `sorting`, `sorting_foreign`) VALUES
-- News 1: New Product Launch (Technology, Companies)
(18, 1, 'tx_news_domain_model_news', 'categories', 0, 1),
(10, 1, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 2: Sustainable Practices (Environment, Companies)
(20, 2, 'tx_news_domain_model_news', 'categories', 0, 1),
(10, 2, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 3: Annual Conference (Economy)
(2, 3, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 4: Partnership Announcement (Companies, Economy)
(10, 4, 'tx_news_domain_model_news', 'categories', 0, 1),
(2, 4, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 5: Research Breakthrough (Science, Research)
(5, 5, 'tx_news_domain_model_news', 'categories', 0, 1),
(44, 5, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 6: Customer Satisfaction (Companies)
(10, 6, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 7: Digital Transformation (Technology, IT)
(18, 7, 'tx_news_domain_model_news', 'categories', 0, 1),
(39, 7, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 8: Innovation Award (Technology, Companies)
(18, 8, 'tx_news_domain_model_news', 'categories', 0, 1),
(10, 8, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 9: Training Program (Education, Labor Market)
(45, 9, 'tx_news_domain_model_news', 'categories', 0, 1),
(11, 9, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 10: Emerging Markets (Economy, Companies)
(2, 10, 'tx_news_domain_model_news', 'categories', 0, 1),
(10, 10, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 11: Cybersecurity (Technology, IT)
(18, 11, 'tx_news_domain_model_news', 'categories', 0, 1),
(39, 11, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 12: Community Outreach (Culture)
(4, 12, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 13: Quarterly Results (Finance, Companies)
(9, 13, 'tx_news_domain_model_news', 'categories', 0, 1),
(10, 13, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 14: Innovation Lab (Technology, Research)
(18, 14, 'tx_news_domain_model_news', 'categories', 0, 1),
(44, 14, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 15: Mobile App Update (Technology, IT)
(18, 15, 'tx_news_domain_model_news', 'categories', 0, 1),
(39, 15, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 16: Sustainability Report (Environment, Environmental Protection)
(20, 16, 'tx_news_domain_model_news', 'categories', 0, 1),
(48, 16, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 17: Expert Panel (Economy)
(2, 17, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 18: Employee Wellness (Health, Labor Market)
(46, 18, 'tx_news_domain_model_news', 'categories', 0, 1),
(11, 18, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 19: Technology Upgrade (Technology)
(18, 19, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 20: Industry Collaboration (Economy)
(2, 20, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 21: Customer Portal (Technology, IT)
(18, 21, 'tx_news_domain_model_news', 'categories', 0, 1),
(39, 21, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 22: International Certification (Companies)
(10, 22, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 23: Data Analytics (Technology, IT)
(18, 23, 'tx_news_domain_model_news', 'categories', 0, 1),
(39, 23, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 24: Regional Office (Companies)
(10, 24, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 25: Supply Chain (Companies, Economy)
(10, 25, 'tx_news_domain_model_news', 'categories', 0, 1),
(2, 25, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 26: AI Integration (Technology, IT)
(18, 26, 'tx_news_domain_model_news', 'categories', 0, 1),
(39, 26, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 27: Scholarship Program (Education)
(45, 27, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 28: Product Safety (Companies)
(10, 28, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 29: Market Research (Economy, Finance)
(2, 29, 'tx_news_domain_model_news', 'categories', 0, 1),
(9, 29, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 30: Green Energy (Environment, Energy, Renewables)
(20, 30, 'tx_news_domain_model_news', 'categories', 0, 1),
(42, 30, 'tx_news_domain_model_news', 'categories', 0, 2),
(43, 30, 'tx_news_domain_model_news', 'categories', 0, 3),

-- News 31: Customer Loyalty (Companies)
(10, 31, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 32: Workplace Diversity (Labor Market)
(11, 32, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 33: Cloud Migration (Technology, IT)
(18, 33, 'tx_news_domain_model_news', 'categories', 0, 1),
(39, 33, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 34: Shareholder Meeting (Finance, Companies)
(9, 34, 'tx_news_domain_model_news', 'categories', 0, 1),
(10, 34, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 35: Product Recall (Companies)
(10, 35, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 36: Innovation Challenge (Innovation, Technology)
(50, 36, 'tx_news_domain_model_news', 'categories', 0, 1),
(18, 36, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 37: Automated Manufacturing (Technology, Mechanical Engineering)
(18, 37, 'tx_news_domain_model_news', 'categories', 0, 1),
(40, 37, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 38: Customer Feedback (Companies)
(10, 38, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 39: Regulatory Compliance (Companies)
(10, 39, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 40: Educational Webinar (Education)
(45, 40, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 41: Quality Assurance (Companies)
(10, 41, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 42: Strategic Vision (Economy, Companies)
(2, 42, 'tx_news_domain_model_news', 'categories', 0, 1),
(10, 42, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 43: Environmental Impact (Environment, Climate Change)
(20, 43, 'tx_news_domain_model_news', 'categories', 0, 1),
(49, 43, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 44: Customer Service Award (Companies)
(10, 44, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 45: Network Infrastructure (Technology, IT)
(18, 45, 'tx_news_domain_model_news', 'categories', 0, 1),
(39, 45, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 46: Industry Forum (Economy)
(2, 46, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 47: Employee Recognition (Labor Market)
(11, 47, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 48: Third-Party Audit (Companies)
(10, 48, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 49: Research Partnership (Science, Research)
(5, 49, 'tx_news_domain_model_news', 'categories', 0, 1),
(44, 49, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 50: Safety Record (Companies, Labor Market)
(10, 50, 'tx_news_domain_model_news', 'categories', 0, 1),
(11, 50, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 51: Customer Advisory Board (Companies)
(10, 51, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 52: Operational Efficiency (Companies, Economy)
(10, 52, 'tx_news_domain_model_news', 'categories', 0, 1),
(2, 52, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 53: Global Standards (Companies)
(10, 53, 'tx_news_domain_model_news', 'categories', 0, 1),

-- News 54: Innovation Showcase (Technology, Innovation)
(18, 54, 'tx_news_domain_model_news', 'categories', 0, 1),
(50, 54, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 55: Supply Agreement (Companies, Economy)
(10, 55, 'tx_news_domain_model_news', 'categories', 0, 1),
(2, 55, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 56: Digital Marketing (Companies, IT)
(10, 56, 'tx_news_domain_model_news', 'categories', 0, 1),
(39, 56, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 57: Trade Show (Economy, Companies)
(2, 57, 'tx_news_domain_model_news', 'categories', 0, 1),
(10, 57, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 58: Knowledge Base (Technology, IT)
(18, 58, 'tx_news_domain_model_news', 'categories', 0, 1),
(39, 58, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 59: Year-End Performance (Finance, Companies)
(9, 59, 'tx_news_domain_model_news', 'categories', 0, 1),
(10, 59, 'tx_news_domain_model_news', 'categories', 0, 2),

-- News 60: Future Plans (Economy, Companies, Innovation)
(2, 60, 'tx_news_domain_model_news', 'categories', 0, 1),
(10, 60, 'tx_news_domain_model_news', 'categories', 0, 2),
(50, 60, 'tx_news_domain_model_news', 'categories', 0, 3);
