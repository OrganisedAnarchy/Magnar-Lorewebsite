<aside class="sidebar-tray" aria-label="Admin sidebar trays">
    <details @if (request()->query('tray') === 'daily-tasks') open @endif>
        <summary aria-label="Daily Tasks" title="Daily Tasks">
            <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
        </summary>
        <section class="sidebar-tray-content" aria-labelledby="daily-tasks-title">
            <h2 id="daily-tasks-title">Daily Tasks</h2>
            <p class="hint">These three lore categories have the fewest entries. Add to them to help balance the setting.</p>

            @foreach ($tasks as $task)
                <section class="panel">
                    <h3>{{ $task['label'] }}</h3>
                    <p>{{ $task['examples'] }}</p>
                    <p class="hint">{{ $task['entries_count'] }} {{ \Illuminate\Support\Str::plural('entry', $task['entries_count']) }} currently in this category.</p>
                    <a class="btn" href="{{ route('admin.entries.create', ['category' => $task['category']]) }}">Add an entry</a>
                </section>
            @endforeach
        </section>
    </details>
</aside>
