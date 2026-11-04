<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArGuide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArGuideController extends Controller
{
    public function index(): View
    {
        $guides = ArGuide::withCount('steps')
            ->latest()
            ->paginate(10);

        return view('admin.ar-guides.index', compact('guides'));
    }

    public function create(): View
    {
        return view('admin.ar-guides.form', [
            'guide' => new ArGuide(),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $modelFile = $request->file('model_file');
        $thumbnailFile = $request->file('thumbnail_file');

        $modelPath = $modelFile
            ? $modelFile->store('ar-guides/models', 'public')
            : null;

        $thumbnailPath = $thumbnailFile
            ? $thumbnailFile->store('ar-guides/thumbnails', 'public')
            : null;

        try {
            DB::transaction(function () use (
                $data, $modelPath, $thumbnailPath
            ) {
                $guide = ArGuide::create([
                    'title' => $data['title'],
                    'slug' => $data['slug'],
                    'description' => $data['description'] ?? null,
                    'model_path' => $modelPath,
                    'thumbnail_path' => $thumbnailPath,
                    'is_active' => $data['is_active'] ?? false,
                ]);

                $this->syncSteps($guide, $data['steps'] ?? []);
            });
        } catch (\Throwable $e) {
            if ($modelPath) {
                Storage::disk('public')->delete($modelPath);
            }

            if ($thumbnailPath) {
                Storage::disk('public')->delete($thumbnailPath);
            }

            throw $e;
        }

        return redirect()
            ->route('admin.ar-guides.index')
            ->with('success', 'Panduan AR berhasil dibuat.');
    }

    public function edit(ArGuide $arGuide): View
    {
        $arGuide->load('steps');

        return view('admin.ar-guides.form', [
            'guide' => $arGuide,
            'isEdit' => true,
        ]);
    }

    public function update(
        Request $request,
        ArGuide $arGuide
    ): RedirectResponse {
        $data = $this->validatedData($request, $arGuide);

        $oldModel = $arGuide->model_path;
        $oldThumbnail = $arGuide->thumbnail_path;

        $newModel = $request->file('model_file')
            ? $request->file('model_file')
                ->store('ar-guides/models', 'public')
            : $oldModel;

        $newThumbnail = $request->file('thumbnail_file')
            ? $request->file('thumbnail_file')
                ->store('ar-guides/thumbnails', 'public')
            : $oldThumbnail;

        try {
            DB::transaction(function () use (
                $arGuide, $data, $newModel, $newThumbnail
            ) {
                $arGuide->update([
                    'title' => $data['title'],
                    'slug' => $data['slug'],
                    'description' => $data['description'] ?? null,
                    'model_path' => $newModel,
                    'thumbnail_path' => $newThumbnail,
                    'is_active' => $data['is_active'] ?? false,
                ]);

                $this->syncSteps($arGuide, $data['steps'] ?? []);
            });
        } catch (\Throwable $e) {
            if ($newModel !== $oldModel && $newModel) {
                Storage::disk('public')->delete($newModel);
            }

            if ($newThumbnail !== $oldThumbnail && $newThumbnail) {
                Storage::disk('public')->delete($newThumbnail);
            }

            throw $e;
        }

        if ($newModel !== $oldModel && $oldModel) {
            Storage::disk('public')->delete($oldModel);
        }

        if ($newThumbnail !== $oldThumbnail && $oldThumbnail) {
            Storage::disk('public')->delete($oldThumbnail);
        }

        return redirect()
            ->route('admin.ar-guides.index')
            ->with('success', 'Panduan AR berhasil diperbarui.');
    }

    public function destroy(ArGuide $arGuide): RedirectResponse
    {
        $modelPath = $arGuide->model_path;
        $thumbnailPath = $arGuide->thumbnail_path;

        $arGuide->delete();

        if ($modelPath) {
            Storage::disk('public')->delete($modelPath);
        }

        if ($thumbnailPath) {
            Storage::disk('public')->delete($thumbnailPath);
        }

        return back()->with('success', 'Panduan AR berhasil dihapus.');
    }

    private function validatedData(
        Request $request,
        ?ArGuide $guide = null
    ): array {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'alpha_dash',
                'max:170',
                Rule::unique('ar_guides', 'slug')
                    ->ignore($guide?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],

            'model_file' => [
                'nullable',
                'file',
                'extensions:glb,gltf',
                'max:20480',
            ],
            'thumbnail_file' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],

            'steps' => ['nullable', 'array', 'max:30'],
            'steps.*.title' => ['required', 'string', 'max:150'],
            'steps.*.instruction' => [
                'required', 'string', 'max:3000',
            ],
            'steps.*.animation_type' => [
                'required',
                Rule::in(['highlight', 'pulse', 'slide', 'none']),
            ],
        ]);
    }

    private function syncSteps(ArGuide $guide, array $steps): void
    {
        $guide->steps()->delete();

        foreach (array_values($steps) as $index => $step) {
            $guide->steps()->create([
                'title' => $step['title'],
                'instruction' => $step['instruction'],
                'step_order' => $index + 1,
                'animation_type' => $step['animation_type'] ?? 'highlight',
            ]);
        }
    }
}