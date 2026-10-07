# WIXWOOD — MASTER WEBSITE REFINEMENT & E-COMMERCE IMPLEMENTATION

You are an expert **product designer, UX/UI designer, full-stack developer, e-commerce architect, conversion strategist, and premium furniture brand designer**.

Your task is to take the **existing WixWood website/project** and turn it into a polished, production-ready digital experience for a premium Nigerian custom furniture and woodworking brand.

## CRITICAL INSTRUCTION

**Continue from the existing WixWood project. Do NOT rebuild the application from scratch.**

First inspect the existing codebase, architecture, database, product data, assets, routes, components, and existing functionality.

Preserve anything that already works.

Only replace functionality when the replacement is genuinely better and fully functional.

Do not create a new architecture unnecessarily.

---

# 1. UNDERSTAND THE BUSINESS

WixWood is a Nigerian woodworking and custom furniture business.

The website should communicate:

* Premium craftsmanship
* Custom furniture
* Natural wood
* Modern Nigerian craftsmanship
* Luxury
* Quality
* Authenticity
* Trust
* Professionalism

Use the supplied WixWood materials and real product photography as the primary source for understanding the business.

Do **not** invent business information.

Never fabricate:

* Prices
* Phone numbers
* Email addresses
* Physical addresses
* Reviews
* Testimonials
* Customer counts
* Awards
* Certifications
* Company history
* Years in business
* Delivery fees
* Product specifications
* Payment credentials
* Social-media statistics

If information is unavailable, either:

1. Use a clearly identifiable configuration placeholder, or
2. Remove the claim entirely.

---

# 2. PRIMARY COMMERCIAL PURPOSE

The website should function as a **premium furniture showroom + genuine e-commerce/order platform where products can be purchased online**.

The customer journey should be:

**Discover WixWood → Browse products → View product → Customize/select options → Add to cart → Checkout → Delivery information → Payment → Payment verification → Order confirmation**

For products that genuinely cannot have a fixed price because they are highly customized, provide:

**Request a Quote / Customize This Product**

instead of inventing a price.

Do not force every product into a fixed-price e-commerce model.

---

# 3. USE REAL WIXWOOD PHOTOGRAPHY

The supplied real WixWood photographs must become the dominant visual language of the website.

Inspect the images carefully and map them intelligently to the appropriate:

* Products
* Categories
* Homepage sections
* Hero sections
* Product galleries
* Craftsmanship sections
* Custom furniture sections
* Related products
* Gallery sections
* About sections

For example, if an image clearly shows a dining table, use it for dining-table content.

If it shows a chair, use it for chair-related content.

If it shows an epoxy/resin table, use it appropriately for that product/category.

Do not randomly reuse photographs.

Do not use generic AI-generated furniture imagery when a real WixWood photograph is available.

---

# 4. REMOVE THE "AI TEMPLATE" FEEL

Audit the entire website and remove anything that makes it feel like:

* An AI-generated website
* A generic startup template
* A design mockup
* A fictional furniture company
* A demo application

Remove or replace where appropriate:

* Generic 2D furniture illustrations
* Cartoon graphics
* Generic vector artwork
* AI-looking decorative graphics
* Placeholder artwork
* Fake testimonials
* Fake reviews
* Fake statistics
* Fake awards
* Fake certifications
* Lorem ipsum
* Fake company history
* Fake product claims
* Fake payment flows
* Buttons that do nothing
* Dead links
* Empty sections
* Placeholder product cards

Simple functional UI icons are fine.

The objective is:

**REAL PREMIUM FURNITURE BRAND — NOT AI DEMO.**

---

# 5. VISUAL DIRECTION

Create the visual language of a premium contemporary furniture showroom.

The design should be:

* Bright
* Spacious
* Elegant
* Editorial
* Sophisticated
* Modern
* Warm
* Premium
* Minimal without feeling empty

### Color hierarchy

**Primary**

* White
* Warm off-white
* Soft cream

**Secondary**

* Natural wood tones
* Warm beige
* Subtle brown

**Contrast**

* Charcoal
* Deep neutral tones

Natural wood should be an accent.

Do NOT make the entire interface brown.

Avoid:

* Large solid-brown backgrounds
* Brown cards everywhere
* Brown buttons everywhere
* Repeating wood textures throughout the UI
* Excessive decorative wood styling

The actual furniture photography should provide the natural wood richness.

Think:

**Luxury furniture showroom + contemporary interior design editorial + premium Nigerian craftsmanship.**

---

# 6. REMOVE EMPTY / UNFINISHED SECTIONS

Audit every page.

Look for:

* Empty grids
* Empty product slots
* Placeholder cards
* Blank image areas
* Empty category cards
* Empty review sections
* Unused promotional cards
* Empty gallery areas
* Broken responsive grids

Never fill an empty section with fake content.

If there is insufficient genuine content:

* Reduce the number of columns
* Dynamically reduce the number of items
* Reorganize the layout
* Hide the section
* Remove it entirely

Every visible section must look intentional and professionally curated.

---

# 7. WEBSITE STRUCTURE

Ensure the existing website provides a coherent experience including, where appropriate:

### Homepage

Include:

* Premium hero
* Clear WixWood value proposition
* Real furniture photography
* Featured products
* Product categories
* Craftsmanship section
* Custom furniture CTA
* Gallery/social section
* Delivery information where verified/configured
* Contact/WhatsApp CTA

### Shop

Include:

* Product grid
* Categories
* Search
* Filtering where useful
* Sorting where useful

### Product page

Include:

* Large real-image gallery
* Product name
* Description
* Price where verified/configured
* Options/variations
* Availability/custom-order status
* Production information where verified
* Delivery information
* Add to Cart
* Buy/Order
* Request a Quote where appropriate
* Related products

### Custom Furniture

Provide a clear process:

1. Tell us what you need
2. Share dimensions/inspiration
3. Discuss materials and design
4. Receive a quote
5. Production begins
6. Delivery

Only make claims supported by WixWood's actual information.

### About

Use only verified business information.

Do not invent a company story.

### Contact

Provide configured:

* Phone
* Email
* WhatsApp
* Instagram
* Location

Do not invent missing details.

---

# 8. PRODUCT DATA

Products should support:

* Name
* Description
* Images
* Gallery
* Category
* Price
* Variations/options
* Customization
* Availability
* Production time where applicable
* Delivery information
* Add to cart
* Request quote where appropriate

Structure product data so WixWood can easily add or edit products later.

Do not hardcode business information throughout the UI.

---

# 9. CART

Implement a real working cart.

It must display:

* Product
* Product image
* Quantity
* Selected options
* Unit price
* Subtotal
* Delivery fee
* Total
* Edit controls
* Remove controls

The cart must persist appropriately during the customer's session.

---

# 10. CHECKOUT

Complete the checkout flow rather than leaving it as a visual prototype.

Collect:

* Full name
* Phone
* Email
* Delivery address
* City
* State
* Order notes
* Customization requirements where relevant

Avoid collecting unnecessary information.

Display:

**Product subtotal

* Delivery fee
  = Total**

The final amount must be calculated and validated server-side.

Never trust a total sent from the browser.

---

# 11. DELIVERY SYSTEM

Make delivery pricing configurable.

Do not invent delivery fees.

Support configuration for:

* Delivery zones
* Delivery fees
* Free delivery rules
* Pickup where applicable

If actual delivery pricing is unavailable, create a clearly identifiable configuration placeholder.

---

# 12. PAYMENT SYSTEM

Prioritize **Paystack** for Nigerian online payments.

Structure the application so **Flutterwave** can also be integrated without redesigning the checkout system.

Payment security is critical.

Never expose secret credentials in frontend code.

Use environment variables such as:

```text
PAYSTACK_PUBLIC_KEY=
PAYSTACK_SECRET_KEY=
FLUTTERWAVE_PUBLIC_KEY=
FLUTTERWAVE_SECRET_KEY=
```

Do not ask the user to paste secret keys into frontend source files.

Payment flow:

1. Customer submits checkout.
2. Server validates the cart.
3. Server calculates the authoritative order total.
4. Order is created with an appropriate pending-payment state.
5. Payment is initialized securely.
6. Customer completes payment.
7. Payment is verified server-side.
8. Only verified payments become `PAID`.
9. Failed/cancelled payments are handled correctly.
10. Customer receives a confirmation page.
11. Admin can see the resulting order/payment status.

A frontend "Payment Successful" message must **never** be treated as proof of payment.

Prevent duplicate payment/order processing where reasonably possible.

---

# 13. ORDER DATABASE

Use the existing database architecture if one exists.

Persist orders with information such as:

* Order ID
* Customer name
* Email
* Phone
* Delivery address
* City
* State
* Products
* Quantities
* Options
* Subtotal
* Delivery fee
* Total
* Payment provider
* Payment reference
* Payment status
* Order status
* Created date
* Updated date
* Customer notes

Suggested order statuses:

* Pending Payment
* Paid
* Processing
* In Production
* Ready for Delivery
* Delivered
* Cancelled

Do not over-engineer the order system.

---

# 14. ADMIN ORDER MANAGEMENT

If an admin panel already exists, extend it.

If one is missing and the current architecture supports it sensibly, create a lightweight admin interface.

The admin should be able to:

* View orders
* Search orders
* Open order details
* View customer information
* View products purchased
* View delivery details
* View payment status
* View payment reference
* Update order status

Keep the interface simple.

This is a furniture business, not a complex SaaS platform.

---

# 15. REVIEW SYSTEM

Implement a trustworthy review system.

Never fabricate reviews.

Use this flow:

**Customer → Submit Review → Pending → Admin Moderation → Approved/Rejected → Approved reviews displayed publicly**

A review should support:

* Star rating
* Customer name
* Review text
* Optional product association
* Submission date
* Status

Statuses:

* Pending
* Approved
* Rejected

Only approved reviews can appear publicly.

The admin should be able to:

* View pending reviews
* Read reviews
* Approve
* Reject
* Delete
* Hide/unpublish approved reviews

Add basic spam/abuse protection.

If a backend/database already exists, integrate reviews into it rather than creating an unnecessary second architecture.

---

# 16. REVIEW UX

The customer-facing review process should be a clean three-step experience:

### Step 1

**Rate your experience**

Select star rating.

### Step 2

**Tell us about your experience**

Enter review.

### Step 3

**Submit your review**

Show a clear confirmation and polished success state.

Make it:

* Mobile-friendly
* Simple
* Trustworthy
* Premium
* Consistent with WixWood

Never display fictional reviews to fill space.

---

# 17. WHATSAPP / CONTACT EXPERIENCE

Because WixWood is a Nigerian furniture business, make contact extremely easy.

Where configured, support:

* WhatsApp
* Phone
* Email
* Request a Quote
* Customize This Product

Keep contact information centralized in configuration.

Do not invent contact details.

If WhatsApp API automation requires an external service, do not pretend it is already configured.

---

# 18. BUSINESS CONFIGURATION

Centralize business-specific configuration.

For example:

```text
BUSINESS_NAME
PHONE
EMAIL
WHATSAPP
INSTAGRAM_URL
ADDRESS
CURRENCY
DELIVERY_ZONES
PAYSTACK_PUBLIC_KEY
PAYSTACK_SECRET_KEY
FLUTTERWAVE_PUBLIC_KEY
FLUTTERWAVE_SECRET_KEY
```

Make product data similarly maintainable.

---

# 19. IMAGE PERFORMANCE

Real WixWood photography is a major part of the experience.

Use:

* Responsive images
* Appropriate image dimensions
* Lazy loading where appropriate
* Good cropping
* Proper aspect ratios
* Descriptive alt text
* Optimized delivery formats where supported

Do not place high-quality furniture photographs into tiny or poorly cropped containers.

---

# 20. RESPONSIVE DESIGN

Test and refine:

* Small phones
* Large phones
* Tablets
* Laptops
* Desktop
* Large displays

Pay particular attention to:

* Navigation
* Product grids
* Product galleries
* Cart
* Checkout
* Payment UI
* Forms
* Order confirmation
* Typography
* Image cropping
* Spacing
* No horizontal overflow

---

# 21. SEO / ACCESSIBILITY / PERFORMANCE

Preserve or improve:

* Semantic HTML
* Page titles
* Meta descriptions
* Product metadata
* Descriptive image alt text
* Accessible form labels
* Keyboard navigation
* Color contrast
* Responsive layouts
* Fast loading
* Proper heading hierarchy

Do not sacrifice usability for visual effects.

---

# 22. CUSTOMER FLOW TEST

Before considering the implementation complete, test the website as a real customer.

Perform this flow:

1. Visit homepage.
2. Browse products.
3. Open a product.
4. View real product photography.
5. Select available options.
6. Add product to cart.
7. Open cart.
8. Proceed to checkout.
9. Enter customer information.
10. Enter delivery information.
11. Review order.
12. Calculate total.
13. Start payment.
14. Verify payment correctly.
15. Update order.
16. Display confirmation.
17. Confirm the order is visible to the admin.

Also test:

* Invalid inputs
* Empty cart
* Failed payment
* Cancelled payment
* Duplicate submission
* Mobile checkout
* Missing configuration values

Do not claim payment is working if credentials have not been configured.

---

# 23. FINAL QUALITY AUDIT

Before finishing, audit the entire application.

### DESIGN

* [ ] Real WixWood imagery dominates
* [ ] Premium furniture aesthetic
* [ ] White/off-white is dominant
* [ ] Wood tones are accents
* [ ] Furniture is visually dominant
* [ ] No AI-template appearance
* [ ] No excessive brown UI
* [ ] No unnecessary illustrations

### CONTENT

* [ ] No fake reviews
* [ ] No fake testimonials
* [ ] No fake statistics
* [ ] No fake awards
* [ ] No fake company history
* [ ] No fake product claims
* [ ] No invented prices
* [ ] No invented business details

### FUNCTIONALITY

* [ ] Product pages work
* [ ] Cart works
* [ ] Checkout works
* [ ] Server-side total calculation works
* [ ] Order creation works
* [ ] Payment initialization works
* [ ] Payment verification works
* [ ] Order confirmation works
* [ ] Admin order management works
* [ ] Review moderation works

### SECURITY

* [ ] Secret keys remain server-side
* [ ] Environment variables are used
* [ ] Prices are validated server-side
* [ ] Payment is verified server-side
* [ ] Sensitive credentials are not committed
* [ ] Duplicate processing is handled appropriately

### RESPONSIVENESS

* [ ] Mobile
* [ ] Tablet
* [ ] Laptop
* [ ] Desktop
* [ ] Large screens
* [ ] No horizontal overflow

---

# 24. IMPORTANT ARCHITECTURE RULE

Do not add technology merely because it sounds impressive.

Before introducing a new backend, database, service, or framework:

**Inspect what already exists.**

Reuse the current architecture wherever practical.

If a backend/database already exists, extend it.

If something is missing, implement the smallest production-appropriate solution.

Do not create duplicate systems.

Do not rebuild working functionality unnecessarily.

---

# 25. IMPLEMENTATION RULE

Do not merely describe what should be built.

**Actually inspect and modify the existing project.**

Do not stop after making visual changes.

Do not leave fake checkout buttons.

Do not leave simulated payment success.

Do not leave unfinished review functionality.

Do not leave dead links.

Do not leave obvious placeholders where real supplied assets are available.

Complete as much of the implementation as the environment allows.

---

# 26. FINAL REPORT

After implementation, provide a concise report with exactly these sections:

## A. What I Changed

Summarize the major changes.

## B. Real WixWood Assets Used

List the supplied imagery/assets that were integrated and where they were used.

## C. What Was Removed

Mention:

* Generic illustrations
* Placeholder content
* Fake/demo elements
* Empty sections
* Other artificial elements removed

## D. E-Commerce Status

Explain:

* Product catalog
* Cart
* Checkout
* Orders
* Delivery
* Payment
* Confirmation
* Admin

## E. Review System

Explain the complete moderation flow.

## F. Payment Configuration Required

List only the credentials/environment variables that I still need to provide.

## G. Remaining Setup

Give me a short checklist containing only actions that genuinely require my involvement.

## H. Testing

Explain how I can safely test the payment system without accidentally treating an unverified transaction as a real paid order.

## I. Remaining Issues

List only genuine unresolved issues or decisions that require my input.

---

# FINAL OBJECTIVE

Make WixWood feel like a **real, premium Nigerian furniture company with a professionally built digital storefront**.

The website should make a visitor think:

> "This is a legitimate premium furniture business I would trust with a ₦500,000–₦5,000,000+ furniture purchase."

The final experience should combine:

**Real WixWood photography

* Premium furniture presentation
* Nigerian craftsmanship
* Clean modern UX
* Genuine e-commerce
* Secure payment
* Reliable order management
* Trustworthy moderated reviews**

Do not optimize for making the website look impressive in a demo.

Optimize for making it **credible, usable, trustworthy, and commercially ready.**
