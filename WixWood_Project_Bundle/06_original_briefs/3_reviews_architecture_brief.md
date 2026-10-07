I need you to clarify the intended purpose and architecture of the Wixxy Wood website, then implement the review system and any required supporting functionality completely.

First, inspect the existing Wixxy Wood website/codebase and determine whether the current implementation is actually a static site or already has any backend/database functionality.

The website's purpose is primarily to act as a premium digital showroom/catalogue for Wixxy Wood, allowing customers to:

Discover and browse Wixxy Wood's furniture and woodworking products
View detailed product information and real product photography
Explore categories and craftsmanship
Build confidence in the brand
Decide what products/services they are interested in
Contact Wixxy Wood / submit enquiries where appropriate

The website itself does NOT need to process or manage customer orders, invoices, billing, or payments.

A separate system handles orders and billing. Do not turn this website into an e-commerce/order-management backend unless the existing project already requires a specific integration with the external ordering system.

1. Review the current review implementation

The current implementation has this limitation:

Because the site is currently static, submitted reviews are sent to Wixxy Wood through WhatsApp/email for the business to read. There is no shared database of reviews and therefore no visitor-facing list of submitted reviews.

This is acceptable as a temporary implementation, but I want you to determine the best complete solution based on the actual purpose of this website.

Do not fabricate reviews, ratings, Trustpilot scores, customer testimonials, or review counts simply to make the website look more established.

2. If a backend is genuinely useful, build the complete review system

If you determine that persistent customer reviews would materially improve the website, implement a simple, production-ready review backend rather than leaving the system as a purely static submission form.

The review flow should be:

Customer → Submit Review → Review stored as Pending → Wixxy Wood/Admin reviews it → Approve or Reject → Approved review becomes visible on website

Include:

Star rating
Customer name
Review text
Optional product/category association
Submission date
Review status: Pending / Approved / Rejected
Admin moderation
Spam/basic abuse protection

Only approved reviews should appear publicly.

Never automatically publish unmoderated submissions as genuine customer reviews.

3. Admin review management

If a backend is added, provide a very simple admin interface where Wixxy Wood can:

View pending reviews
Read the full review
Approve review
Reject review
Delete inappropriate submissions
View approved reviews
Hide/unpublish a previously approved review if necessary

Keep this extremely simple. This is a furniture showcase website, not a complex SaaS platform.

4. Make the review system automatic after setup

I want you to minimize the amount of technical work I have to do manually.

Build the system so that after deployment:

Customer submits review → system stores it → admin receives notification → admin approves → review automatically appears on the website.

If email notifications are possible, implement them using environment variables for the recipient/configuration.

If WhatsApp notification is appropriate but requires an external API, clearly separate that external dependency rather than pretending it is already configured.

5. Show me exactly what I need to do

This is important.

After building the system, give me a simple “What You Need To Do” checklist containing only the things that genuinely require my action.

For example:

Create/provide the production database.
Add the database credentials to .env.
Create the admin account.
Add the production email credentials if email notifications are enabled.
Deploy the application.

Do not tell me to manually perform technical tasks that you can automate or configure in the codebase yourself.

If you can create setup scripts, migrations, seeders, or an installation command to automate something, do that instead.

6. Do NOT add unnecessary e-commerce functionality

This distinction is critical:

Wixxy Wood's website is the showroom/catalogue and customer-facing brand experience.

It does not need to become the system responsible for:

Taking orders
Generating invoices
Billing customers
Processing payments
Managing order fulfilment
Managing inventory

Those functions are handled by another system.

The website should therefore focus on:

Brand → Products → Craftsmanship → Customer confidence → Product discovery → Enquiry/contact → Reviews

If there is an existing link/integration to the external ordering system, preserve it and make the handoff clear and reliable.

7. Keep Wixxy Wood's visual direction

Maintain the existing premium Wixxy Wood design, including the recent refinement:

White/off-white should dominate
Natural wood/brown should be an accent rather than the primary UI color
Deep charcoal/dark neutrals for contrast
Premium furniture-showroom aesthetic
Real Wixxy Wood product photography
Spacious layouts
Strong typography
Sophisticated Nigerian craftsmanship positioning

Do not use fake reviews or generic trust content to fill empty sections.

Also remove empty grids/placeholder review cards rather than leaving visibly unfinished areas.

8. Architecture decision

Before implementing anything, make a clear technical decision:

Option A — Keep it static: If a backend genuinely provides little benefit for this site's purpose, keep the review submission lightweight and explain why.

Option B — Add a lightweight backend: If persistent moderated reviews are valuable, implement the smallest sensible backend/database required to make them work properly.

Prefer the simplest solution that fully satisfies the actual purpose of the website.

Do not introduce a large backend architecture just for the sake of having one.

9. Final requirement

Do not just explain what could be built. Build it.

Inspect the existing project, make the architectural decision, implement the complete solution, test it, and remove any broken/unfinished review functionality.

Then give me:

A. What you changed
B. How the complete review flow now works
C. What is automated
D. What I personally need to configure/do
E. Any external services or credentials still required

The final result should be a complete, professional Wixxy Wood showroom website with a trustworthy review system, while leaving actual orders, billing, and payment processing to the separate system that handles those functions.