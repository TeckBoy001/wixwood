<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // ---- Public: approved reviews the live site displays ----
    public function index()
    {
        $reviews = Review::approved()
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'rating', 'name', 'piece', 'comment', 'created_at']);

        return response()->json(['reviews' => $reviews]);
    }

    // ---- Public: submit a review (always lands as "pending") ----
    public function store(Request $request)
    {
        // Honeypot: a hidden field real customers never fill in.
        if ($request->filled('_hp')) {
            return response()->json(['ok' => true]); // pretend success, drop silently
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'name' => ['nullable', 'string', 'max:80'],
            'piece' => ['nullable', 'string', 'max:120'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $review = Review::create([
            'rating' => $validated['rating'],
            'name' => trim($validated['name'] ?? '') ?: 'Anonymous',
            'piece' => $validated['piece'] ?? null,
            'comment' => trim($validated['comment']),
            'status' => 'pending',
        ]);

        // TODO (optional, mirrors the Node version): send an email/notification
        // to WixWood here when a new review comes in, using Laravel's
        // Notification system, once mail credentials are configured.

        return response()->json(['ok' => true, 'id' => $review->id], 201);
    }

    // ---- Admin ----
    public function adminIndex(Request $request)
    {
        $query = Review::query()->orderByDesc('created_at');

        $status = $request->query('status');
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        return response()->json(['reviews' => $query->get()]);
    }

    public function approve(Review $review)
    {
        $review->update(['status' => 'approved']);

        return response()->json(['ok' => true]);
    }

    public function reject(Review $review)
    {
        $review->update(['status' => 'rejected']);

        return response()->json(['ok' => true]);
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return response()->json(['ok' => true]);
    }
}
