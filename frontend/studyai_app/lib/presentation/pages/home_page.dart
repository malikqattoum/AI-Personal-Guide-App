import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:file_picker/file_picker.dart';
import '../../core/constants/app_colors.dart';
import '../blocs/auth/auth_cubit.dart';
import '../blocs/auth/auth_state.dart';
import '../blocs/document/document_cubit.dart';
import '../blocs/document/document_state.dart';
import 'login_page.dart';
import 'chat_page.dart';
import 'flashcards_page.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    context.read<DocumentCubit>().loadDocuments();
  }

  Future<void> _pickPdf() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf'],
    );

    if (result != null && result.files.single.path != null) {
      final file = result.files.single;
      context.read<DocumentCubit>().uploadPdf(
            filePath: file.path!,
            fileName: file.name,
          );
    }
  }

  Future<void> _addYoutube() async {
    final urlController = TextEditingController();

    final result = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Add YouTube Video'),
        content: TextField(
          controller: urlController,
          decoration: const InputDecoration(
            labelText: 'YouTube URL',
            hintText: 'https://youtube.com/watch?v=...',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, urlController.text),
            child: const Text('Add'),
          ),
        ],
      ),
    );

    if (result != null && result.isNotEmpty) {
      context.read<DocumentCubit>().addYoutube(url: result);
    }
  }

  void _showAddOptions() {
    showModalBottomSheet(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.picture_as_pdf, color: AppColors.primary),
              title: const Text('Upload PDF'),
              onTap: () {
                Navigator.pop(context);
                _pickPdf();
              },
            ),
            ListTile(
              leading: const Icon(Icons.play_circle_outline, color: AppColors.secondary),
              title: const Text('Add YouTube Video'),
              onTap: () {
                Navigator.pop(context);
                _addYoutube();
              },
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return BlocListener<AuthCubit, AuthState>(
      listener: (context, state) {
        if (state is AuthUnauthenticated) {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(builder: (_) => const LoginPage()),
          );
        }
      },
      child: Scaffold(
        appBar: AppBar(
          title: const Text('StudyAI'),
          actions: [
            IconButton(
              icon: const Icon(Icons.logout),
              onPressed: () => context.read<AuthCubit>().logout(),
            ),
          ],
        ),
        body: IndexedStack(
          index: _currentIndex,
          children: [
            _buildDocumentsTab(),
            const FlashcardsPage(),
            const ChatPage(),
          ],
        ),
        floatingActionButton: _currentIndex == 0
            ? FloatingActionButton.extended(
                onPressed: _showAddOptions,
                icon: const Icon(Icons.add),
                label: const Text('Add Content'),
              )
            : null,
        bottomNavigationBar: BottomNavigationBar(
          currentIndex: _currentIndex,
          onTap: (index) => setState(() => _currentIndex = index),
          items: const [
            BottomNavigationBarItem(
              icon: Icon(Icons.folder_outlined),
              activeIcon: Icon(Icons.folder),
              label: 'Documents',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.style_outlined),
              activeIcon: Icon(Icons.style),
              label: 'Flashcards',
            ),
            BottomNavigationBarItem(
              icon: Icon(Icons.chat_bubble_outline),
              activeIcon: Icon(Icons.chat_bubble),
              label: 'Chat',
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDocumentsTab() {
    return BlocBuilder<DocumentCubit, DocumentState>(
      builder: (context, state) {
        if (state is DocumentLoading) {
          return const Center(child: CircularProgressIndicator());
        }

        final documents = state is DocumentLoaded
            ? state.documents
            : state is DocumentUploading
                ? state.documents
                : <dynamic>[];

        if (documents.isEmpty) {
          return Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.description_outlined,
                  size: 80,
                  color: AppColors.textHint.withValues(alpha: 0.5),
                ),
                const SizedBox(height: 16),
                Text(
                  'No documents yet',
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        color: AppColors.textSecondary,
                      ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Upload a PDF or add a YouTube video\nto get started',
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                const SizedBox(height: 24),
                ElevatedButton.icon(
                  onPressed: _showAddOptions,
                  icon: const Icon(Icons.add),
                  label: const Text('Add Content'),
                ),
              ],
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: () => context.read<DocumentCubit>().loadDocuments(),
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: documents.length,
            itemBuilder: (context, index) {
              final doc = documents[index];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: ListTile(
                  leading: Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: doc.isYoutube
                          ? AppColors.secondary.withValues(alpha: 0.1)
                          : AppColors.primary.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(
                      doc.isYoutube
                          ? Icons.play_circle_fill
                          : Icons.picture_as_pdf,
                      color: doc.isYoutube
                          ? AppColors.secondary
                          : AppColors.primary,
                    ),
                  ),
                  title: Text(
                    doc.title,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  subtitle: Text(
                    doc.status == 'completed'
                        ? 'Ready'
                        : doc.status == 'processing'
                            ? 'Processing...'
                            : doc.status,
                    style: TextStyle(
                      color: doc.status == 'completed'
                          ? AppColors.success
                          : AppColors.warning,
                    ),
                  ),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () {
                    // Navigate to document detail
                  },
                ),
              );
            },
          ),
        );
      },
    );
  }
}
