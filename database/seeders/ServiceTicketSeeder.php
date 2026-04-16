<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceTicketSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $serviceAdminId = 6; // Mohammed - Service
        $superAdminId = 1;

        $tickets = [
            [
                'ticket_number' => 'TKT-2026-0001',
                'customer_id' => 1,
                'product_id' => 1,
                'issue_description' => 'Belt buckle came loose within 2 weeks of purchase. Customer requests replacement buckle.',
                'priority' => 'high',
                'status' => 'closed',
                'days_ago' => 80,
                'closed_days_ago' => 75,
                'resolution_notes' => 'Replacement buckle fitted. Customer satisfied.',
            ],
            [
                'ticket_number' => 'TKT-2026-0002',
                'customer_id' => 3,
                'product_id' => 3,
                'issue_description' => 'Wallet stitching coming apart at the fold. Purchased 3 months ago.',
                'priority' => 'medium',
                'status' => 'closed',
                'days_ago' => 60,
                'closed_days_ago' => 55,
                'resolution_notes' => 'Re-stitched wallet under warranty. Customer happy with repair quality.',
            ],
            [
                'ticket_number' => 'TKT-2026-0003',
                'customer_id' => 8,
                'product_id' => 16,
                'issue_description' => 'Leather sole peeling off on Oxford shoes. Happened after first rain exposure.',
                'priority' => 'high',
                'status' => 'in_progress',
                'days_ago' => 20,
                'closed_days_ago' => null,
                'resolution_notes' => null,
            ],
            [
                'ticket_number' => 'TKT-2026-0004',
                'customer_id' => 12,
                'product_id' => 8,
                'issue_description' => 'Color fading on suede jacket after dry cleaning. Customer claims jacket was dry-cleaned as per care instructions.',
                'priority' => 'medium',
                'status' => 'in_progress',
                'days_ago' => 15,
                'closed_days_ago' => null,
                'resolution_notes' => null,
            ],
            [
                'ticket_number' => 'TKT-2026-0005',
                'customer_id' => 17,
                'product_id' => 22,
                'issue_description' => 'Laptop bag zipper stuck and not closing fully. Zipper teeth misaligned.',
                'priority' => 'low',
                'status' => 'open',
                'days_ago' => 8,
                'closed_days_ago' => null,
                'resolution_notes' => null,
            ],
            [
                'ticket_number' => 'TKT-2026-0006',
                'customer_id' => 2,
                'product_id' => 9,
                'issue_description' => 'Bomber jacket zipper broken after 1 month of use. Customer requests full replacement.',
                'priority' => 'high',
                'status' => 'open',
                'days_ago' => 5,
                'closed_days_ago' => null,
                'resolution_notes' => null,
            ],
            [
                'ticket_number' => 'TKT-2026-0007',
                'customer_id' => 6,
                'product_id' => 24,
                'issue_description' => 'Handbag handle stitching coming loose. Minor thread pull observed.',
                'priority' => 'low',
                'status' => 'open',
                'days_ago' => 3,
                'closed_days_ago' => null,
                'resolution_notes' => null,
            ],
            [
                'ticket_number' => 'TKT-2026-0008',
                'customer_id' => 9,
                'product_id' => 6,
                'issue_description' => 'Wrong size polo t-shirt delivered. Ordered L but received M. Customer requests exchange.',
                'priority' => 'medium',
                'status' => 'closed',
                'days_ago' => 40,
                'closed_days_ago' => 38,
                'resolution_notes' => 'Exchanged with correct size. Courier picked up wrong item.',
            ],
        ];

        foreach ($tickets as $t) {
            $openedAt = $now->copy()->subDays($t['days_ago']);
            $closedAt = $t['closed_days_ago'] ? $now->copy()->subDays($t['closed_days_ago']) : null;

            $ticketId = DB::table('service_tickets')->insertGetId([
                'ticket_number' => $t['ticket_number'],
                'customer_id' => $t['customer_id'],
                'product_id' => $t['product_id'],
                'issue_description' => $t['issue_description'],
                'priority' => $t['priority'],
                'status' => $t['status'],
                'assigned_to' => $serviceAdminId,
                'opened_at' => $openedAt,
                'closed_at' => $closedAt,
                'resolution_notes' => $t['resolution_notes'],
                'created_by' => $superAdminId,
                'updated_by' => null,
                'deleted_by' => null,
                'created_at' => $openedAt,
                'updated_at' => $closedAt ?? $openedAt,
                'deleted_at' => null,
            ]);

            // Add comments
            $comments = [];

            // First comment: acknowledgment
            $comments[] = [
                'service_ticket_id' => $ticketId,
                'comment' => 'Ticket received and assigned to service team for review.',
                'created_by' => $superAdminId,
                'created_at' => $openedAt,
                'updated_at' => $openedAt,
            ];

            // Second comment: investigation
            if (in_array($t['status'], ['in_progress', 'closed'])) {
                $comments[] = [
                    'service_ticket_id' => $ticketId,
                    'comment' => 'Inspected the product. Issue confirmed. Proceeding with repair/replacement.',
                    'created_by' => $serviceAdminId,
                    'created_at' => $openedAt->copy()->addDays(1),
                    'updated_at' => $openedAt->copy()->addDays(1),
                ];
            }

            // Third comment: resolution (for closed tickets)
            if ($t['status'] === 'closed' && $closedAt) {
                $comments[] = [
                    'service_ticket_id' => $ticketId,
                    'comment' => 'Issue resolved. '.($t['resolution_notes'] ?? 'Customer notified.'),
                    'created_by' => $serviceAdminId,
                    'created_at' => $closedAt,
                    'updated_at' => $closedAt,
                ];
            }

            DB::table('service_ticket_comments')->insert($comments);
        }
    }
}
