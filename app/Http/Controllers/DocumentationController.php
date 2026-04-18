<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    public function index()
    {
        $sections   = $this->getSections();
        $categories = [];
        foreach ($sections as $slug => $sec) {
            $sec['slug'] = $slug;
            $categories[$sec['category']][$slug] = $sec;
        }

        return view('documentation.index', compact('sections', 'categories'));
    }

    public function section(string $section)
    {
        $sections = $this->getSections();

        if (! array_key_exists($section, $sections)) {
            abort(404);
        }

        $current         = $sections[$section];
        $current['slug'] = $section;

        $categories = [];
        foreach ($sections as $slug => $sec) {
            $sec['slug'] = $slug;
            $categories[$sec['category']][$slug] = $sec;
        }

        return view('documentation.section', compact('current', 'sections', 'categories'));
    }

    public function show(?string $page = null)
    {
        if (empty($page) || $page === 'index') {
            return $this->index();
        }

        return $this->section($page);
    }

    public function search(Request $request)
    {
        $query = strtolower(trim($request->input('q', '')));

        if (strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $results  = [];
        $sections = $this->getSections();

        foreach ($sections as $slug => $section) {
            if (str_contains(strtolower($section['title']), $query)) {
                $results[] = ['title' => $section['title'], 'section' => $section['category'], 'slug' => $slug, 'icon' => $section['icon']];
            }
            foreach ($section['topics'] ?? [] as $topic) {
                $searchable = strtolower(
                    $topic['title'] . ' ' . ($topic['content'] ?? '') . ' ' .
                    implode(' ', $topic['list'] ?? []) . ' ' .
                    implode(' ', $topic['steps'] ?? [])
                );
                if (str_contains($searchable, $query)) {
                    $results[] = ['title' => $topic['title'], 'section' => $section['title'], 'slug' => $slug, 'icon' => $section['icon']];
                }
            }
        }

        $unique = [];
        foreach ($results as $r) {
            $key = $r['slug'] . '|' . $r['title'];
            $unique[$key] = $unique[$key] ?? $r;
        }

        return response()->json(['results' => array_values(array_slice($unique, 0, 15))]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  ERP USER DOCUMENTATION
    // ─────────────────────────────────────────────────────────────────────────

    private function getSections(): array
    {
        return [

            // ═══════════════════════════════════════════════════════
            //  GETTING STARTED
            // ═══════════════════════════════════════════════════════

            'overview' => [
                'title'    => 'Overview',
                'icon'     => 'home',
                'category' => 'Getting Started',
                'summary'  => 'What ALTechnics ERP is, who uses it, and what you can do with it.',
                'topics'   => [
                    [
                        'title'   => 'What is ALTechnics ERP?',
                        'content' => 'ALTechnics ERP is a complete business management system for your company. It connects every part of your business — from the first conversation with a potential customer all the way to getting paid. Instead of managing leads in one spreadsheet, quotations in another, and invoices in a third, everything lives in one place and flows automatically from one stage to the next.',
                    ],
                    [
                        'title'   => 'What Can You Do?',
                        'list'    => [
                            'CRM — Track leads (potential customers), follow up, and convert them into customers',
                            'Quotations — Prepare and send professional price quotes to customers',
                            'Sales Orders — Confirm and manage customer orders',
                            'Invoices — Automatically generate invoices from sales orders',
                            'Payments — Record and track all customer payments',
                            'Purchasing — Raise purchase orders to vendors and receive goods',
                            'Inventory — Track stock levels across multiple warehouses in real time',
                            'Products — Manage your complete product catalog with pricing',
                            'Service Tickets — Log and resolve customer complaints and service requests',
                            'Reports — View sales, inventory, customer, and payment reports',
                            'Access Control — Give each team member access only to what they need',
                        ],
                    ],
                    [
                        'title'   => 'How the Business Flow Works',
                        'content' => 'The system is designed around your natural sales cycle:',
                        'list'    => [
                            '1. You receive an enquiry → create a Lead',
                            '2. You prepare a price → create a Quotation and send it',
                            '3. Customer agrees → convert Quotation to a Sales Order',
                            '4. Goods are dispatched → generate an Invoice from the Sales Order',
                            '5. Customer pays → record the Payment against the Invoice',
                            '6. Need to restock → raise a Purchase Order to your Vendor',
                            '7. Goods arrive → receive them and stock is updated automatically',
                        ],
                        'tip'     => 'Each step is connected. Converting a quotation to an order takes one click — you never re-enter the same data twice.',
                    ],
                    [
                        'title'   => 'Who Uses This System?',
                        'list'    => [
                            'Super Admin — Full access to all features, settings, and user management',
                            'Sales Team — Manages leads, quotations, and sales orders',
                            'Accounts Team — Handles invoices, payments, and financial reports',
                            'Purchase Team — Manages vendors, purchase orders, and goods receipt',
                            'Warehouse Team — Monitors inventory, stock movements, and adjustments',
                            'Service Team — Handles customer service tickets and complaints',
                        ],
                    ],
                ],
            ],

            'first-time-setup' => [
                'title'    => 'First-Time Setup',
                'icon'     => 'rocket',
                'category' => 'Getting Started',
                'summary'  => 'The recommended order to set up your ERP before you start using it.',
                'topics'   => [
                    [
                        'title'   => 'Step 1 — Log In',
                        'content' => 'Open the admin portal at /admin/login. Enter your email and password and click Sign In. If this is your first login, change your password immediately via your profile menu in the top-right corner.',
                        'warning' => 'Never share your admin password. Each team member should have their own login account.',
                    ],
                    [
                        'title'   => 'Step 2 — Set Up Company Profile',
                        'content' => 'This is the very first thing to do. Your company name, address, phone, email, GSTIN, and logo appear on every PDF you generate — quotations, invoices, and purchase orders.',
                        'steps'   => [
                            'Go to Settings (from the sidebar)',
                            'Fill in Company Name, Address, Phone, and Email',
                            'Enter your GSTIN (used on invoices for GST compliance)',
                            'Upload your company logo',
                            'Click Save',
                        ],
                        'warning' => 'Do this before generating any PDFs. Missing fields will show blank on printed documents.',
                    ],
                    [
                        'title'   => 'Step 3 — Add Your Products',
                        'content' => 'Before you can create quotations or orders, your products need to be in the system.',
                        'steps'   => [
                            'Go to Products → Categories and create your product categories (e.g., Leather Bags, Accessories)',
                            'Go to Products → All Products and add each product',
                            'For each product: enter name, code, HSN code, unit, and sale/purchase rate',
                            'Set the minimum stock level for low-stock alerts',
                        ],
                        'tip'     => 'You can also type a product description manually when creating a quotation, so products are optional — but having them in the catalog saves time.',
                    ],
                    [
                        'title'   => 'Step 4 — Add Customers and Vendors',
                        'steps'   => [
                            'Go to CRM → Customers and add your existing customer base',
                            'For each customer: name, company, phone, email, address, and GSTIN',
                            'Go to Purchasing → Vendors and add your suppliers',
                            'For each vendor: name, company, phone, email, and address',
                        ],
                    ],
                    [
                        'title'   => 'Step 5 — Set Up Warehouses',
                        'content' => 'Even if you have just one location, you need at least one warehouse in the system for inventory tracking.',
                        'steps'   => [
                            'Go to Settings → Warehouses',
                            'Click "+ New Warehouse"',
                            'Enter a name (e.g., "Main Store") and a short code (e.g., "MS")',
                            'Save',
                        ],
                    ],
                    [
                        'title'   => 'Step 6 — Create Team Accounts',
                        'steps'   => [
                            'Go to Admin → Roles and create roles for your team (e.g., Sales Manager, Accounts, Warehouse)',
                            'Assign the right permissions to each role',
                            'Go to Admin → Admin Users and create a login account for each team member',
                            'Assign their role so they only see what they need',
                        ],
                        'tip'     => 'You can start using the system yourself first and add team accounts later.',
                    ],
                    [
                        'title'   => 'Recommended Setup Order (Quick Reference)',
                        'list'    => [
                            '1. Settings → Company Profile (name, logo, GSTIN)',
                            '2. Products → Categories',
                            '3. Products → Add products with pricing',
                            '4. CRM → Customers',
                            '5. Purchasing → Vendors',
                            '6. Settings → Warehouses',
                            '7. Admin → Roles → create roles',
                            '8. Admin → Admin Users → create team accounts',
                            '9. You are ready to start! Begin with CRM → Leads or Sales → Quotations',
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            //  CRM
            // ═══════════════════════════════════════════════════════

            'leads' => [
                'title'    => 'Leads',
                'icon'     => 'users',
                'category' => 'CRM',
                'summary'  => 'Track potential customers from first contact to conversion.',
                'topics'   => [
                    [
                        'title'   => 'What is a Lead?',
                        'content' => 'A lead is a person or company that has shown interest in buying from you but has not yet placed an order. The system lets you track every lead, record conversations, and move them through stages until they become a customer — or you mark them as lost.',
                    ],
                    [
                        'title'   => 'Lead Stages',
                        'list'    => [
                            'New — Just enquired, no contact made yet',
                            'Contacted — You have spoken to them or sent an email',
                            'Qualified — They have confirmed interest and budget',
                            'Proposal — You have sent them a quotation',
                            'Negotiation — You are discussing price or terms',
                            'Won — Deal closed, convert to customer',
                            'Lost — They did not proceed',
                        ],
                    ],
                    [
                        'title'   => 'Creating a Lead',
                        'steps'   => [
                            'Go to CRM → Leads → click "+ New Lead"',
                            'Enter the contact\'s Name, Company, Phone, and Email',
                            'Select Source — how did they find you? (Walk-in, Website, Referral, Cold Call)',
                            'Set Priority: Low, Medium, or High',
                            'Add any notes about their requirement',
                            'Click Save',
                        ],
                    ],
                    [
                        'title'   => 'Updating Lead Status',
                        'content' => 'From the leads list, click the coloured status badge on any row. A dropdown appears — select the new status. No page reload needed. You can also update status from inside the lead detail page.',
                    ],
                    [
                        'title'   => 'Kanban Board View',
                        'content' => 'Go to CRM → Leads → Kanban to see all your leads arranged in columns by status. This gives you a visual pipeline — you can instantly see how many leads are at each stage.',
                    ],
                    [
                        'title'   => 'Converting a Lead to Customer',
                        'content' => 'Once a lead is won, you convert them to a proper customer record with one click.',
                        'steps'   => [
                            'Open the lead detail page',
                            'Click "Convert to Customer"',
                            'Confirm — a Customer record is created automatically with all the lead\'s details',
                            'You can now create quotations and orders for this customer',
                        ],
                        'tip'     => 'The lead is automatically marked as "Won" when you convert it.',
                    ],
                ],
            ],

            'customers' => [
                'title'    => 'Customers',
                'icon'     => 'briefcase',
                'category' => 'CRM',
                'summary'  => 'Manage your customer database — contacts, addresses, and transaction history.',
                'topics'   => [
                    [
                        'title'   => 'Adding a Customer',
                        'steps'   => [
                            'Go to CRM → Customers → click "+ New Customer"',
                            'Enter Name, Company Name, Phone, and Email',
                            'Fill in the address: Street, City, State, Pincode',
                            'Enter GSTIN if the customer is GST-registered (printed on invoices)',
                            'Set Status to Active',
                            'Click Save',
                        ],
                    ],
                    [
                        'title'   => 'Viewing a Customer\'s History',
                        'content' => 'Click on any customer\'s name to open their profile. You will see all their transactions in one place:',
                        'list'    => [
                            'All quotations you have sent them',
                            'All confirmed sales orders',
                            'All invoices generated',
                            'All payments received',
                            'All service tickets they have raised',
                        ],
                    ],
                    [
                        'title'   => 'Deactivating a Customer',
                        'content' => 'Use the toggle switch on the customer list to mark a customer as Inactive. Inactive customers are hidden from dropdowns when creating new quotations or orders, but all their past records are preserved.',
                    ],
                    [
                        'title'   => 'Searching and Filtering',
                        'content' => 'Use the search bar at the top of the Customers list to find a customer by name, company, phone, or email. Results update as you type.',
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            //  SALES
            // ═══════════════════════════════════════════════════════

            'quotations' => [
                'title'    => 'Quotations',
                'icon'     => 'document',
                'category' => 'Sales',
                'summary'  => 'Create, send, and manage price quotations for your customers.',
                'topics'   => [
                    [
                        'title'   => 'What is a Quotation?',
                        'content' => 'A quotation (also called a price estimate) is a formal document you send to a customer showing the products, quantities, rates, taxes, and total amount before they place an order. The customer can accept it, reject it, or negotiate.',
                    ],
                    [
                        'title'   => 'Quotation Statuses',
                        'list'    => [
                            'Draft — Being prepared, not yet sent (shows DRAFT watermark on PDF)',
                            'Sent — You have shared it with the customer',
                            'Accepted — Customer has approved it',
                            'Rejected — Customer declined',
                            'Expired — The validity date has passed',
                        ],
                    ],
                    [
                        'title'   => 'Creating a Quotation',
                        'steps'   => [
                            'Go to Sales → Quotations → click "+ New Quotation"',
                            'Select the Customer from the dropdown',
                            'The Quotation Number is auto-generated (e.g., QUO-2026-0049)',
                            'Set the Quotation Date and Valid Until date',
                            'Click "+ Add Item" to add a product line',
                            'For each item: select product (or type a description), enter quantity and rate',
                            'Optionally set item-level discount % and tax %',
                            'Set an overall Discount (fixed amount or percentage) if needed',
                            'Set overall Tax % if needed',
                            'Add Terms & Conditions and Notes at the bottom',
                            'Click Save — the quotation is saved as Draft',
                        ],
                    ],
                    [
                        'title'   => 'How Amounts Are Calculated',
                        'list'    => [
                            'Each line: Gross = Quantity × Rate',
                            'Each line: After Discount = Gross minus item-level discount',
                            'Each line: Line Total = After Discount plus item-level tax',
                            'Subtotal = Sum of all line totals',
                            'Overall Discount = Fixed amount OR percentage of subtotal (cannot exceed 100%)',
                            'After Discount = Subtotal minus Overall Discount',
                            'Overall Tax = Percentage of After Discount amount',
                            'Grand Total = After Discount + Overall Tax',
                        ],
                    ],
                    [
                        'title'   => 'Sending a Quotation',
                        'steps'   => [
                            'Open the quotation',
                            'Click "Download PDF" to get a professional PDF to share with the customer',
                            'Once sent, click "Mark as Sent" to update the status',
                        ],
                        'tip'     => 'The PDF includes your company logo, address, GSTIN, all line items, totals, and terms. Make sure Settings → Company Profile is filled in.',
                    ],
                    [
                        'title'   => 'Updating Status After Customer Response',
                        'content' => 'Once you hear back from the customer, update the status:',
                        'list'    => [
                            'Customer accepted → click "Mark as Accepted"',
                            'Customer rejected → click "Mark as Rejected" (you can reopen to edit and resend)',
                            'No response by validity date → mark as Expired (can be reopened)',
                        ],
                    ],
                    [
                        'title'   => 'Converting to a Sales Order',
                        'content' => 'When the customer accepts, convert the quotation to a Sales Order in one click.',
                        'steps'   => [
                            'Open the accepted quotation',
                            'Click "Convert to Sales Order"',
                            'A Sales Order is created with all the same items and amounts',
                            'You are taken directly to the new Sales Order',
                        ],
                        'tip'     => 'You never have to re-enter the items — they carry over automatically.',
                    ],
                    [
                        'title'   => 'Cloning a Quotation',
                        'content' => 'Need to send a similar quote to a different customer, or create a revised version? Click "Clone" on any quotation to create an exact copy with a new number and Draft status.',
                    ],
                ],
            ],

            'sales-orders' => [
                'title'    => 'Sales Orders',
                'icon'     => 'shopping-cart',
                'category' => 'Sales',
                'summary'  => 'Manage confirmed customer orders from processing to delivery.',
                'topics'   => [
                    [
                        'title'   => 'What is a Sales Order?',
                        'content' => 'A sales order is a confirmed order from a customer. It is the internal document that tells your team what to prepare, pack, and dispatch. It is usually created by converting an accepted quotation, but can also be created directly.',
                    ],
                    [
                        'title'   => 'Sales Order Statuses',
                        'list'    => [
                            'Pending — Order received, waiting to be processed',
                            'Processing — Team is preparing or packing the order',
                            'Shipped — Goods have been dispatched',
                            'Delivered — Customer has received the goods',
                            'Cancelled — Order was cancelled',
                        ],
                    ],
                    [
                        'title'   => 'Creating a Sales Order Directly',
                        'steps'   => [
                            'Go to Sales → Sales Orders → click "+ New Order"',
                            'Select Customer and set Order Date',
                            'Add line items with product, quantity, rate, discount, and tax',
                            'Add any notes or delivery address',
                            'Click Save',
                        ],
                        'tip'     => 'Converting from a quotation is faster and ensures the customer approved the exact amounts. Direct creation is useful for repeat or phone orders.',
                    ],
                    [
                        'title'   => 'Updating Order Status',
                        'content' => 'As the order moves through your workflow, update the status to keep your team informed:',
                        'list'    => [
                            'When you start packing → change to Processing',
                            'When you hand over to courier → change to Shipped',
                            'When confirmed delivered → change to Delivered',
                        ],
                    ],
                    [
                        'title'   => 'Generating an Invoice from a Sales Order',
                        'steps'   => [
                            'Open the Sales Order',
                            'Click "Generate Invoice"',
                            'An invoice is created automatically with the same items and amounts',
                            'The invoice is linked to the sales order',
                        ],
                        'warning' => 'An invoice can only be generated once per sales order. If corrections are needed, edit the invoice directly.',
                    ],
                ],
            ],

            'invoices' => [
                'title'    => 'Invoices',
                'icon'     => 'receipt',
                'category' => 'Sales',
                'summary'  => 'Generate, manage, and download professional tax invoices.',
                'topics'   => [
                    [
                        'title'   => 'What is an Invoice?',
                        'content' => 'An invoice is the official billing document you send to the customer requesting payment. It includes your company details, customer\'s GSTIN, an itemized list with HSN codes, tax amounts, and the total payable. An invoice is legally required for GST transactions.',
                    ],
                    [
                        'title'   => 'Invoice Statuses',
                        'list'    => [
                            'Draft — Created but not yet sent to customer',
                            'Sent — Invoice has been shared with the customer',
                            'Partial — Part of the invoice amount has been received',
                            'Paid — Full payment has been received',
                            'Overdue — Payment not received by due date',
                            'Cancelled — Invoice was cancelled',
                        ],
                    ],
                    [
                        'title'   => 'Downloading Invoice PDF',
                        'steps'   => [
                            'Open the invoice',
                            'Click "Download PDF"',
                            'A professional invoice PDF is generated with your company logo and all details',
                            'Send it to your customer via email or WhatsApp',
                        ],
                        'tip'     => 'The invoice PDF includes your bank account details from Settings so customers know where to transfer payment.',
                    ],
                    [
                        'title'   => 'Creating an Invoice Manually',
                        'content' => 'If you need to invoice without a sales order (e.g., for a service charge):',
                        'steps'   => [
                            'Go to Sales → Invoices → click "+ New Invoice"',
                            'Select Customer, set Invoice Date and Due Date',
                            'Add line items',
                            'Set discount and tax if applicable',
                            'Add payment terms and notes',
                            'Save as Draft, then mark as Sent once shared',
                        ],
                    ],
                    [
                        'title'   => 'Tracking Invoice Payments',
                        'content' => 'When you record a payment (in Sales → Payments) and link it to the invoice, the invoice status updates automatically to Partial or Paid based on the amount received.',
                    ],
                ],
            ],

            'payments' => [
                'title'    => 'Payments',
                'icon'     => 'wallet',
                'category' => 'Sales',
                'summary'  => 'Record and track all customer payments against invoices.',
                'topics'   => [
                    [
                        'title'   => 'Recording a Payment',
                        'steps'   => [
                            'Go to Sales → Payments → click "+ New Payment"',
                            'Select the Customer',
                            'Select the Invoice you are receiving payment for',
                            'Enter the Amount received',
                            'Set the Payment Date',
                            'Select Payment Mode: Cash, Cheque, Bank Transfer, UPI, or Online',
                            'Add a reference number (cheque number, UTR, or UPI transaction ID)',
                            'Click Save',
                        ],
                        'tip'     => 'Always add a reference number — it helps reconcile payments with your bank statement later.',
                    ],
                    [
                        'title'   => 'Payment Modes',
                        'list'    => [
                            'Cash — Physical cash received in hand',
                            'Cheque — Add the cheque number as the reference',
                            'Bank Transfer / NEFT / RTGS — Add the UTR number as reference',
                            'UPI — Add the UPI transaction ID as reference',
                            'Online — Credit card, debit card, or payment gateway',
                        ],
                    ],
                    [
                        'title'   => 'Viewing Payment History',
                        'content' => 'Go to Sales → Payments to see all payments. Filter by customer name or date range. Each payment shows the invoice it was applied to and the payment mode. You can also see payment history in the customer\'s profile page.',
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            //  PURCHASING
            // ═══════════════════════════════════════════════════════

            'vendors' => [
                'title'    => 'Vendors',
                'icon'     => 'truck',
                'category' => 'Purchasing',
                'summary'  => 'Manage your suppliers and their contact details.',
                'topics'   => [
                    [
                        'title'   => 'Adding a Vendor',
                        'steps'   => [
                            'Go to Purchasing → Vendors → click "+ New Vendor"',
                            'Enter Name, Company, Phone, and Email',
                            'Add address and GSTIN',
                            'Set Status to Active',
                            'Click Save',
                        ],
                    ],
                    [
                        'title'   => 'Vendor Information',
                        'list'    => [
                            'Name — Your contact person at the supplier',
                            'Company — The supplier\'s business name',
                            'Phone and Email — Contact details',
                            'Address — Delivery and billing address',
                            'GSTIN — Used on purchase orders for tax compliance',
                        ],
                    ],
                    [
                        'title'   => 'Deactivating a Vendor',
                        'content' => 'Use the toggle on the vendor list to deactivate vendors you no longer work with. They will be hidden from new purchase order dropdowns but their past orders are preserved.',
                    ],
                ],
            ],

            'purchase-orders' => [
                'title'    => 'Purchase Orders',
                'icon'     => 'clipboard',
                'category' => 'Purchasing',
                'summary'  => 'Raise purchase orders to vendors and receive goods into stock.',
                'topics'   => [
                    [
                        'title'   => 'What is a Purchase Order?',
                        'content' => 'A purchase order (PO) is the document you send to a vendor to formally request goods. When the goods arrive, you receive them against the PO and the stock levels update automatically.',
                    ],
                    [
                        'title'   => 'Creating a Purchase Order',
                        'steps'   => [
                            'Go to Purchasing → Purchase Orders → click "+ New PO"',
                            'Select the Vendor',
                            'Set PO Date and Expected Delivery Date',
                            'Add line items: select product, enter quantity and unit price',
                            'Add any notes or special instructions',
                            'Click Save — the PO number is auto-generated (e.g., PO-2026-0012)',
                        ],
                        'prerequisites' => [
                            ['label' => 'Vendor', 'description' => 'At least one vendor must exist'],
                            ['label' => 'Products', 'description' => 'Products to order must be in the catalog'],
                        ],
                    ],
                    [
                        'title'   => 'Receiving Goods',
                        'content' => 'When the vendor delivers the goods, record the receipt to update your stock.',
                        'steps'   => [
                            'Open the Purchase Order',
                            'Click "Receive Goods"',
                            'For each item, enter the quantity actually received',
                            'You can receive partial quantities — the PO stays open until everything arrives',
                            'Click Save Receipt',
                            'Stock levels are automatically increased for the received items',
                        ],
                        'tip'     => 'If a vendor delivers less than ordered, receive what arrived. Come back and do another receipt when the rest is delivered.',
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            //  PRODUCTS & INVENTORY
            // ═══════════════════════════════════════════════════════

            'products' => [
                'title'    => 'Products',
                'icon'     => 'package',
                'category' => 'Products & Inventory',
                'summary'  => 'Manage your product catalog, categories, pricing, and HSN codes.',
                'topics'   => [
                    [
                        'title'   => 'Product Categories',
                        'content' => 'Organise your products into categories. For example: Leather Bags → Tote Bags. Categories are hierarchical — you can have sub-categories under a parent.',
                        'steps'   => [
                            'Go to Products → Categories → click "+ New Category"',
                            'Enter a name and optionally select a parent category',
                            'Set a sort order for how it appears in lists',
                            'Save',
                        ],
                    ],
                    [
                        'title'   => 'Adding a Product',
                        'steps'   => [
                            'Go to Products → All Products → click "+ New Product"',
                            'Enter Name and a unique Code / SKU',
                            'Select the Category',
                            'Enter the HSN Code (8-digit code required for GST invoices)',
                            'Set the default Unit (pcs, kg, metres, box, etc.)',
                            'Enter the default Sale Rate (selling price)',
                            'Enter the Purchase Rate (your buying price)',
                            'Set the Minimum Stock Level — you will be alerted when stock falls to this level',
                            'Set Status to Active',
                            'Save',
                        ],
                    ],
                    [
                        'title'   => 'What is the HSN Code?',
                        'content' => 'HSN (Harmonized System of Nomenclature) is a classification code assigned to every product by the government for GST purposes. It is printed on your invoices. Your CA or supplier can help you find the right HSN code for each product.',
                        'warning' => 'GST invoices legally require HSN codes. Fill this in correctly for every product.',
                    ],
                    [
                        'title'   => 'Deactivating a Product',
                        'content' => 'Use the toggle switch on the product list to deactivate discontinued products. They will be hidden from quotation and order dropdowns but all past records using them are preserved.',
                    ],
                ],
            ],

            'inventory' => [
                'title'    => 'Inventory',
                'icon'     => 'warehouse',
                'category' => 'Products & Inventory',
                'summary'  => 'Track stock levels, movements, and adjustments across your warehouses.',
                'topics'   => [
                    [
                        'title'   => 'Viewing Current Stock',
                        'content' => 'Go to Inventory → Overview to see the current stock quantity for every product in every warehouse. Use the warehouse and category filters to narrow down the view.',
                        'tip'     => 'Stock levels update automatically when you receive a purchase order or fulfill a sales order. You do not need to update them manually.',
                    ],
                    [
                        'title'   => 'Stock Movements',
                        'content' => 'Go to Inventory → Movements to see the full audit trail of every stock change. Each record shows:',
                        'list'    => [
                            'Which product and warehouse',
                            'Whether it was stock coming IN or going OUT',
                            'The quantity and the date',
                            'The reason (purchase order received, sales order fulfilled, or manual adjustment)',
                        ],
                    ],
                    [
                        'title'   => 'Low Stock Alerts',
                        'content' => 'Go to Inventory → Low Stock to see all products where the current quantity has fallen to or below the minimum stock level you set on the product. This is your reorder list.',
                        'tip'     => 'Check this page regularly or set a routine (e.g., every Monday) to raise purchase orders for anything showing low stock.',
                    ],
                    [
                        'title'   => 'Manual Stock Adjustment',
                        'content' => 'Use this when you need to correct the stock count after a physical count, account for damaged goods, or record a discrepancy.',
                        'steps'   => [
                            'Go to Inventory → Adjust Stock',
                            'Select the Product and Warehouse',
                            'Choose whether you are adding stock or removing stock',
                            'Enter the Quantity',
                            'Enter a Reason (e.g., "Physical count correction", "Damaged goods")',
                            'Click Save',
                        ],
                        'warning' => 'Manual adjustments should only be used for corrections. Normal stock changes (purchases and sales) happen automatically.',
                    ],
                    [
                        'title'   => 'Warehouses',
                        'content' => 'If you have multiple physical locations (main store, branch, showroom), you can track stock separately in each. Go to Settings → Warehouses to add locations. Each stock movement is tied to a specific warehouse.',
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            //  SERVICE
            // ═══════════════════════════════════════════════════════

            'service-tickets' => [
                'title'    => 'Service Tickets',
                'icon'     => 'wrench',
                'category' => 'Service',
                'summary'  => 'Log, track, and resolve customer service requests and complaints.',
                'topics'   => [
                    [
                        'title'   => 'What is a Service Ticket?',
                        'content' => 'A service ticket is a logged record of a customer complaint, repair request, or support query. Every interaction and update is tracked with a timeline so nothing falls through the cracks.',
                    ],
                    [
                        'title'   => 'Creating a Ticket',
                        'steps'   => [
                            'Go to Service → Tickets → click "+ New Ticket"',
                            'Select the Customer',
                            'Optionally select the Product the issue relates to',
                            'Enter a short Title describing the issue',
                            'Write a detailed Description',
                            'Set Priority: Low, Medium, High, or Urgent',
                            'Assign it to the team member who will handle it',
                            'Click Save — ticket status starts as Open',
                        ],
                    ],
                    [
                        'title'   => 'Ticket Statuses',
                        'list'    => [
                            'Open — Just logged, not yet being worked on',
                            'In Progress — Being actively worked on',
                            'Resolved — Issue has been fixed, waiting for customer confirmation',
                            'Closed — Fully resolved and closed',
                        ],
                    ],
                    [
                        'title'   => 'Adding Comments and Updates',
                        'content' => 'Open a ticket and click "+ Add Comment" to log any update — a phone call, a site visit, a diagnosis note. Comments are timestamped and show who added them. Use this to build a full history of what was done.',
                    ],
                    [
                        'title'   => 'Resolving and Closing a Ticket',
                        'steps'   => [
                            'Open the ticket',
                            'Add a comment describing the resolution',
                            'Fill in the Resolution Notes field',
                            'Change status to Resolved',
                            'Once the customer confirms, change to Closed',
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            //  REPORTS
            // ═══════════════════════════════════════════════════════

            'reports' => [
                'title'    => 'Reports',
                'icon'     => 'chart',
                'category' => 'Reports',
                'summary'  => 'View and export sales, inventory, customer, purchase, and payment reports.',
                'topics'   => [
                    [
                        'title'   => 'Available Reports',
                        'list'    => [
                            'Sales Report — Revenue by period, customer, or product. Shows quotations, orders, and invoices together.',
                            'Inventory Report — Current stock levels per product and warehouse, movement history, and low-stock summary.',
                            'Customer Report — All customers with their total purchase value and outstanding balance.',
                            'Purchase Report — All purchase orders by vendor and date, total spend, and goods received.',
                            'Payment Report — All payments received with date, customer, mode (cash, UPI, bank transfer), and reference.',
                        ],
                    ],
                    [
                        'title'   => 'Filtering a Report',
                        'content' => 'Every report has filters. Use them to narrow down what you see:',
                        'list'    => [
                            'Date Range — Show only records from a specific period (e.g., this month, last quarter)',
                            'Customer — Show records for one specific customer',
                            'Vendor — Show records for one specific vendor',
                            'Warehouse — Show inventory for one location',
                            'Payment Mode — Filter by cash, UPI, bank transfer, etc.',
                        ],
                        'tip'     => 'Always set your filters first, then export. The export will include only what is currently showing on screen.',
                    ],
                    [
                        'title'   => 'Exporting Reports',
                        'list'    => [
                            'Excel (.xlsx) — Click "Export Excel" to download a spreadsheet you can open in Excel or Google Sheets',
                            'PDF — Click "Export PDF" to download a printable document',
                        ],
                    ],
                    [
                        'title'   => 'Dashboard Summary',
                        'content' => 'The main dashboard (shown right after login) gives you a quick snapshot:',
                        'list'    => [
                            'Total active customers and open leads',
                            'Total quotations and their combined value',
                            'Monthly revenue from paid invoices',
                            'Number of products with low stock',
                            'Recent sales orders and activity feed',
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            //  ADMIN & SETTINGS
            // ═══════════════════════════════════════════════════════

            'roles-permissions' => [
                'title'    => 'Roles & Permissions',
                'icon'     => 'shield',
                'category' => 'Admin & Settings',
                'summary'  => 'Control what each team member can see and do in the system.',
                'topics'   => [
                    [
                        'title'   => 'How It Works',
                        'content' => 'Every admin user is assigned a Role. A role is a collection of permissions. For example, a "Sales Manager" role might have permission to create and edit quotations but not to delete them or access settings. When a user tries to do something they don\'t have permission for, the system shows a 403 Access Denied message.',
                    ],
                    [
                        'title'   => 'Creating a Role',
                        'steps'   => [
                            'Go to Admin → Roles → click "+ New Role"',
                            'Enter a name for the role (e.g., "Sales Manager", "Accounts", "Warehouse Staff")',
                            'Check the permissions this role should have — they are grouped by module',
                            'Use "Select All" on a module group to quickly grant all permissions for that module',
                            'Click Save',
                        ],
                        'tip'     => 'Start with a "Super Admin" role that has all permissions, and more restricted roles for each department.',
                    ],
                    [
                        'title'   => 'Permissions Available',
                        'list'    => [
                            'Customers — view, create, edit, delete',
                            'Leads — view, create, edit, delete',
                            'Quotations — view, create, edit, delete',
                            'Sales Orders — view, create, edit, delete',
                            'Invoices — view, create, edit, delete',
                            'Payments — view, create, edit, delete',
                            'Products — view, create, edit, delete',
                            'Inventory — view, create, edit, delete',
                            'Vendors — view, create, edit, delete',
                            'Purchase Orders — view, create, edit, delete',
                            'Service Tickets — view, create, edit, delete',
                            'Reports — view',
                            'Settings — view, edit',
                            'Admin Users — view, create, edit, delete',
                            'Roles — view, create, edit, delete',
                        ],
                    ],
                    [
                        'title'   => 'Assigning a Role to a User',
                        'steps'   => [
                            'Go to Admin → Admin Users',
                            'Open the user you want to update',
                            'In the Roles section, select the appropriate role(s)',
                            'Save — the change takes effect on their next login',
                        ],
                        'warning' => 'Never remove the Super Admin role from all users — you could lock everyone out of settings.',
                    ],
                ],
            ],

            'admin-users' => [
                'title'    => 'Admin Users',
                'icon'     => 'user-shield',
                'category' => 'Admin & Settings',
                'summary'  => 'Create and manage login accounts for your team.',
                'topics'   => [
                    [
                        'title'   => 'Creating a Team Account',
                        'steps'   => [
                            'Go to Admin → Admin Users → click "+ New Admin"',
                            'Enter the person\'s Name and Email address',
                            'Set a strong Password (they should change it on first login)',
                            'Assign their Role',
                            'Set Status to Active',
                            'Click Save',
                            'Share the email and password with them securely',
                        ],
                        'tip'     => 'Ask each new user to change their password immediately after first login.',
                    ],
                    [
                        'title'   => 'Changing Your Password',
                        'steps'   => [
                            'Click your name or avatar in the top-right corner of any page',
                            'Select "Change Password"',
                            'Enter your current password',
                            'Enter and confirm your new password',
                            'Click Update',
                        ],
                    ],
                    [
                        'title'   => 'Deactivating a User',
                        'content' => 'If a team member leaves, use the toggle switch on the admin users list to deactivate their account immediately. They will not be able to log in. Their past records (orders they created, etc.) are fully preserved.',
                    ],
                ],
            ],

            'settings' => [
                'title'    => 'Settings',
                'icon'     => 'cog',
                'category' => 'Admin & Settings',
                'summary'  => 'Configure your company profile, document numbering, currency, and terms.',
                'topics'   => [
                    [
                        'title'   => 'Company Profile',
                        'content' => 'Your company details appear on every PDF you generate. Keep this up to date.',
                        'list'    => [
                            'Company Name — Shown at the top of every document',
                            'Address — Full address printed on quotes and invoices',
                            'Phone and Email — Contact details on documents',
                            'GSTIN — Your GST registration number (required on invoices)',
                            'Company Logo — Appears on PDFs and the login page',
                        ],
                        'steps'   => [
                            'Go to Settings from the sidebar',
                            'Fill in all the fields in the Company Profile section',
                            'Upload your logo (PNG or JPG, square format preferred)',
                            'Click Save',
                        ],
                    ],
                    [
                        'title'   => 'Document Numbering',
                        'content' => 'The system auto-generates numbers for every document. You can customise the prefix:',
                        'list'    => [
                            'Quotation — Default prefix: QUO (generates QUO-2026-0001, QUO-2026-0002...)',
                            'Sales Order — Default prefix: SO',
                            'Invoice — Default prefix: INV',
                            'Purchase Order — Default prefix: PO',
                        ],
                        'tip'     => 'Change prefixes at the start of a new financial year if you want a fresh series (e.g., QUO-2027-0001).',
                    ],
                    [
                        'title'   => 'Currency',
                        'content' => 'Set your currency symbol (e.g., ₹ for Indian Rupee) and currency code (e.g., INR). This symbol appears on all amount displays and PDFs throughout the system.',
                    ],
                    [
                        'title'   => 'Default Terms & Conditions',
                        'content' => 'Enter your standard payment terms and conditions here (e.g., "Payment due within 30 days"). This text automatically fills in when you create a new quotation or invoice — you can override it per document.',
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════════
            //  HELP
            // ═══════════════════════════════════════════════════════

            'troubleshooting' => [
                'title'    => 'Troubleshooting & FAQ',
                'icon'     => 'bug',
                'category' => 'Help',
                'summary'  => 'Answers to common questions and solutions to frequent issues.',
                'topics'   => [
                    [
                        'title'   => 'I forgot my password',
                        'content' => 'Ask your Super Admin to reset your password:',
                        'steps'   => [
                            'Super Admin goes to Admin → Admin Users',
                            'Opens your account',
                            'Clicks "Edit" and sets a new password',
                            'Saves and shares the new password with you',
                            'Log in and change it immediately',
                        ],
                    ],
                    [
                        'title'   => 'I cannot see a menu or button',
                        'content' => 'Your account does not have permission for that feature.',
                        'steps'   => [
                            'Note down what you are trying to do',
                            'Ask your Super Admin',
                            'They will go to Admin → Roles, edit your role, and add the missing permission',
                            'You may need to log out and back in for the change to take effect',
                        ],
                    ],
                    [
                        'title'   => 'A dropdown is showing no options',
                        'content' => 'This means the data it depends on has not been created yet. Common examples:',
                        'list'    => [
                            'Customer dropdown empty → Go to CRM → Customers and add customers first',
                            'Product dropdown empty → Go to Products and add products first',
                            'Vendor dropdown empty → Go to Purchasing → Vendors and add vendors first',
                            'Warehouse dropdown empty → Go to Settings → Warehouses and add one',
                        ],
                    ],
                    [
                        'title'   => 'Stock levels look wrong',
                        'content' => 'Stock is calculated automatically from all receipts and shipments. Check:',
                        'list'    => [
                            'Did you receive the purchase order? Open it and check if goods receipt was recorded',
                            'Was the sales order fulfilled? Check if a stock-out movement was created',
                            'Go to Inventory → Movements and filter by the product to see the full history',
                            'If the count is genuinely wrong, use Inventory → Adjust Stock to correct it',
                        ],
                    ],
                    [
                        'title'   => 'The PDF has blank fields (no company name, no logo)',
                        'steps'   => [
                            'Go to Settings → Company Profile',
                            'Fill in the missing fields',
                            'Upload the logo if it is missing',
                            'Save',
                            'Regenerate the PDF — the fields will now be populated',
                        ],
                    ],
                    [
                        'title'   => 'I accidentally created a duplicate record',
                        'content' => 'You can delete duplicate customers, vendors, or products as long as no transactions are linked to them. If a transaction is linked, you cannot delete it — instead, deactivate it using the toggle switch so it no longer appears in dropdowns.',
                    ],
                    [
                        'title'   => 'How do I undo a wrong payment entry?',
                        'content' => 'There is no automatic undo. If you entered the wrong amount or wrong invoice:',
                        'steps'   => [
                            'Go to Sales → Payments',
                            'Find the incorrect payment',
                            'Delete it if it was entered by mistake and no other records depend on it',
                            'Create a new correct payment entry',
                        ],
                        'warning' => 'Contact your Super Admin if you are unsure — do not create duplicate entries trying to fix it.',
                    ],
                    [
                        'title'   => 'The page is loading slowly',
                        'list'    => [
                            'Press Ctrl+Shift+R (Windows) or Cmd+Shift+R (Mac) to do a hard refresh',
                            'Clear your browser cache (browser Settings → Clear browsing data)',
                            'Try a different browser (Chrome or Edge recommended)',
                            'Check your internet connection',
                            'If only one page is slow, it may be loading a large report — apply filters to reduce the data',
                        ],
                    ],
                ],
            ],
        ];
    }
}
