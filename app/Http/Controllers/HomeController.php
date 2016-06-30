<?php namespace App\Http\Controllers;

use Auth;
use App\Models\Article;
use App\Models\Event;
use App\Models\Forum\Post;
use App\Models\Forum\Thread;
use App\Models\Session;
use App\Models\User;
use App\Models\World;
use DB;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Return the homepage view.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(World $world, Request $request)
    {
        $events = Auth::check() ? Event::upcoming() : Event::publicOnly()->upcoming();

        $threads = Thread::with(['author', 'posts'])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->filter(function ($thread) use ($request) {
                return !$thread->category->private || Auth::check() && $request->user()->can('view', $thread->category);
            })->take(5);

        $posts = Post::where('post_id', '!=', null)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->filter(function ($post) use ($request) {
                return !$post->thread->category->private || Auth::check() && $request->user()->can('view', $post->thread->category);
            })->take(5);

        return view('page.home', [
            'world' => $world->slug,
            'upcomingEvents' => $events->orderBy('ends_at', 'desc')->limit(5)->get(),
            'newUsers' => User::active()->orderBy('created_at', 'desc')->limit(5)->get(),
            'onlineUsers' => Session::authenticated()->groupBy('user_id')->recent()->limit(10)->get(),
            'newThreads' => $threads,
            'newPosts' => $posts,
            'articles' => Article::published()->orderBy('published_at', 'desc')->paginate()
        ]);
    }
}
