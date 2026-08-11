<x-app-layout>

    <div class="section fade-up">
        <div class="ocms-page-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1>Manajemen User</h1>
                <p>{{ $users->count() }} user terdaftar dalam sistem</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn-primary">+ Tambah User</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success fade-up">✅ {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-error fade-up">
            @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
        </div>
    @endif

    <div class="glass-card fade-up table-scroll" style="padding: 0;">
        <table class="ocms-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>Role</th>
                    <th>Terdaftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $index => $user)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="font-weight: 600; color: var(--text-primary);">{{ $user->name }}</td>
                    <td class="mono">{{ $user->nik }}</td>
                    <td>
                        @foreach($user->roles as $role)
                            <span class="badge badge-purple">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td style="color: var(--text-muted); font-size: 0.8rem;">{{ $user->created_at->format('d M Y') }}</td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
                            <a href="{{ route('admin.users.password.edit', $user->id) }}" class="btn-secondary btn-sm" style="font-size: 0.7rem;" title="Ganti password user ini">🔑 Password</a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Hapus user {{ $user->name }}?')" style="margin: 0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm" style="font-size: 0.7rem;">Hapus</button>
                            </form>
                            @else
                                <span class="badge badge-cyan">Anda</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</x-app-layout>
