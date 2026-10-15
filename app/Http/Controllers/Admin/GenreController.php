<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public const SWATCHES = ['#0A9BFF', '#18C6D8', '#4F86C6', '#7A6CFF', '#B783FF', '#2CB58A'];

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), Genre::KINDS) ? $request->query('tab') : 'music';
        $counts = Genre::selectRaw('kind, count(*) n')->groupBy('kind')->pluck('n', 'kind');
        $list = Genre::where('kind', $tab)->orderBy('position')->get();

        return view('admin.genres', ['tab' => $tab, 'list' => $list, 'counts' => $counts, 'swatches' => self::SWATCHES]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $n = Genre::where('kind', $data['kind'])->count();
        Genre::create($data + ['position' => $n]);

        return redirect()->route('genres.index', ['tab' => $data['kind']])->with('toast', ["“{$data['name']}” saved", 'ok']);
    }

    public function update(Request $request, Genre $genre)
    {
        $data = $this->validated($request);
        $genre->update(collect($data)->except('kind')->all());

        return redirect()->route('genres.index', ['tab' => $genre->kind])->with('toast', ["“{$genre->name}” saved", 'ok']);
    }

    public function destroy(Genre $genre)
    {
        $genre->delete();

        return redirect()->route('genres.index', ['tab' => $genre->kind])->with('toast', ["“{$genre->name}” removed", 'bad']);
    }

    public function reorder(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
        foreach ($ids as $i => $id) {
            Genre::whereKey($id)->update(['position' => $i]);
        }

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'kind' => 'required|in:'.implode(',', Genre::KINDS),
            'name' => 'required|string|max:40',
            'color' => 'required|in:'.implode(',', self::SWATCHES),
            'art_seed' => 'required|integer|min:0|max:11',
            'art_style' => 'required|integer|min:0|max:4',
        ]);
    }
}
